# AWS S3 Upload (REDCap External Module)

Upload files from REDCap data entry forms directly to an Amazon S3 bucket.

Files go straight from the user's browser to S3 and never pass through the REDCap server. That means REDCap's file upload size limit doesn't apply: a single file can be up to **5 GB**. REDCap records only where the file was stored, in a companion text field on the form.

## Requirements

- REDCap 17.0.1 or later
- PHP 8.0 or later
- An S3 bucket, plus an IAM access key that can write to it (see [AWS setup](#aws-setup))

If you're on an older REDCap version, use [v1.0.1](https://github.com/AndrewPoppe/aws_upload/releases/tag/v1.0.1).

## Installation

1. Download the latest release from the [Releases page](https://github.com/AndrewPoppe/aws_upload/releases).
2. Unzip it into your REDCap `modules` directory, naming the folder `aws_upload_v<version>`. For example:

   ```sh
   unzip aws_upload-1.0.2.zip
   mv aws_upload-1.0.2 <redcap>/modules/aws_upload_v1.0.2
   ```

3. In **Control Center → External Modules → Manage**, enable the module.
4. In each project that will use it, enable the module and configure it (see [Project configuration](#project-configuration)).

### Upgrading

Install the new version alongside the old one, then switch versions in **Control Center → External Modules**. Project settings carry over as long as the folder name starts with `aws_upload_`.

Older installs that used a folder named `AWSStorage_v…` have a different module prefix. Their settings won't carry over, so you'll need to re-enter them in each project.

When upgrading to v1.0.2, also check the [ACL setting](#about-the-acl-setting) in each project.

## Project configuration

Open **External Modules** in the project, then click **Configure** next to *AWSStorage*.

| Setting | What to enter |
| --- | --- |
| **AWS S3 bucket name** | The bucket name only, e.g. `spinup-001042-abc-ct-data`. |
| **AWS S3 bucket ID** | The IAM **access key ID** (starts with `AKIA`). |
| **AWS S3 bucket access key** | The IAM **secret access key** that belongs to that key ID. |
| **AWS S3 bucket region** | The bucket's region, e.g. `us-east-1`. |
| **AWS S3 bucket access control list** | Usually blank. See [About the ACL setting](#about-the-acl-setting). |
| **Base directory** | The top-level folder (S3 key prefix) for this project's uploads, e.g. `T4 Follow-Up`. Uploads can only be written under this prefix. |

### About the ACL setting

Leave this blank unless you know the bucket needs an ACL.

- **ACLs disabled.** New S3 buckets default to Object Ownership: *Bucket owner enforced*, which turns ACLs off. If you set an ACL on one of these buckets, every upload fails with `AccessControlListNotSupported`.
- **ACLs enabled.** You can enter a canned ACL such as `private` or `bucket-owner-full-control`. Avoid `public-read-write`, the value the old example suggested: it makes every uploaded file publicly readable and writable. If the bucket blocks public access, S3 refuses the upload instead.

## Setting up fields

The module only acts on fields that follow a naming convention. For each upload, add **two fields to the same instrument**:

1. A **File Upload** field whose name starts with `aws_`, e.g. `aws_eeg`.
2. A **Text Box** field with the same name prefixed by `log_`, e.g. `log_aws_eeg`.

Both are required. The upload fails if the `log_` field is missing from the instrument.

Example data dictionary rows:

| Variable / Field Name | Form Name | Field Type | Field Label |
| --- | --- | --- | --- |
| `aws_eeg` | `visit_data` | file | EEG recording |
| `log_aws_eeg` | `visit_data` | text | EEG recording – S3 location |

File upload fields that don't start with `aws` work exactly as they do without the module.

### Where files are stored

Each file is written to:

```
<base directory>/<field segment>/<instrument name>/<original file name>
```

- **field segment** is the part of the field name after the first underscore, up to the next underscore. For `aws_eeg` it's `eeg`; for `aws_eeg_zip` it's also `eeg`.
- **instrument name** is the instrument's variable name with underscores replaced by spaces. For `visit_data` it's `visit data`.

For example, uploading `subject01.zip` to `aws_eeg` on `visit_data` with base directory `T4 Follow-Up` stores:

```
T4 Follow-Up/eeg/visit data/subject01.zip
```

The record ID and event are **not** part of the path. If two uploads have the same file name, field segment, and instrument, the second one overwrites the first in S3. Use file names that are unique, such as names that include the participant ID.

## Using it

1. Open the instrument on a data entry form and click **Upload file** on the `aws_` field. The dialog shows "(Max file size: 5 GB)".
2. Choose a file and click **Upload file**. The file is sent to S3.
3. When the upload finishes, the dialog shows "File was successfully uploaded!" and closes. The `log_` field is filled in with the file's S3 location.
4. **Save the form.** Until you do, the S3 location isn't saved to the record, even though the file is already in S3.

### What REDCap does and doesn't store

- The REDCap file upload field itself stays empty. REDCap has no copy of the file and shows no download link for it.
- Getting the file back requires access to the S3 bucket.
- Clearing the `log_` field, or deleting the record, does **not** delete the file from S3.

### Limitations

- **Data entry forms only.** Uploads to `aws_` fields don't work on surveys. Put these fields on instruments that aren't enabled as surveys.
- Maximum file size is 5 GB, the limit for a single S3 browser upload.
- The module doesn't check file names or file types.

## AWS setup

### IAM permissions

Give the access key the smallest set of permissions it needs: permission to write objects under the project's base directory.

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": "s3:PutObject",
      "Resource": "arn:aws:s3:::YOUR-BUCKET-NAME/YOUR-BASE-DIRECTORY/*"
    }
  ]
}
```

If you set an ACL in the module, also allow `s3:PutObjectAcl`.

### Bucket settings

- **CORS:** no configuration is needed. The browser submits a regular form to S3 rather than making a JavaScript request.
- **Encryption:** every upload asks for S3-managed server-side encryption (`AES256`), so bucket policies that require encryption are satisfied.
- **Metadata:** every object also gets an `x-amz-meta-uuid` value.


## Troubleshooting

To see why an upload failed, open the browser's developer tools and go to the **Network** tab. Find the `POST` request to `<bucket>.s3.amazonaws.com` and look at its **Response**. S3 returns an XML error with a `<Code>` and `<Message>`.

| S3 error / symptom | Cause | Fix |
| --- | --- | --- |
| `InvalidAccessKeyId` | The access key ID doesn't exist. It may have been deleted, deactivated, or rotated. | Create a new access key and update **AWS S3 bucket ID** and **AWS S3 bucket access key**. |
| `SignatureDoesNotMatch` | The secret access key doesn't match the key ID. | Re-enter the secret access key. |
| `AccessControlListNotSupported` | An ACL is set, but the bucket has ACLs disabled. | Clear **AWS S3 bucket access control list**. |
| `AccessDenied` – *Invalid according to Policy* | The upload didn't match the signed policy. | Reload the page and try again. The policy is signed when the page loads and expires after one week. |
| `AccessDenied` (no policy message) | The IAM user lacks `s3:PutObject` on that path, or a bucket policy blocks the upload, e.g. a public ACL on a bucket that blocks public access. | Check the IAM and bucket policies. |
| The dialog still shows REDCap's normal size limit | The field name doesn't start with `aws`, or the module isn't enabled in this project. | Rename the field or enable the module. |
| Clicking **Upload file** does nothing | The matching `log_` field is missing from the instrument, or the instrument is being filled out as a survey. | Add the `log_` field, or use the instrument as a data entry form. |
| The upload hangs, then the user is logged out | You're running a module version older than v1.0.2 on a newer REDCap. | Upgrade the module. |

## Authors

- Adam Newton (YCMI), original author
- Andrew Poppe (Yale University), current maintainer

## License

[GNU General Public License v2](LICENSE)
