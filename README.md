# aws_upload
This REDCap EM to enable file uploads to AWS S3 bucket.
To use this module create a file upload field in a project where the field name starts with "aws", also create a text field with the same name except that it starts with "log_aws" to record a log of the file name uploaded.
It includes additional filename validation for ABC-CT.

# deployment
To deploy this module download the [repo](https://git.yale.edu/ajn48/aws_upload/archive/master.zip) and unzip it;

```unzip aws_upload-master.zip```
   
This will create an "aws_upload-master" directory. Move this directory to the REDCap External Modules directory;

```mv aws_upload-master <redcap>/modules/AWSStorage_v0.0.3```

Where ```<redcap>/modules``` is REDCap External Modules directory and ```v0.0.3``` is the version.

Then in the REDCap instance enable the external module for the particular project. In that project EM configuration the following parameters for the S3 bucket can be set;
 * AWS S3 bucket name
 * AWS S3 bucket ID
 * AWS S3 bucket access key
 * AWS S3 bucket region, e.g us-east-1
 * AWS S3 bucket access control list, e.g. public-read-write
 * Base directory (optional) e.g. Test 
  
