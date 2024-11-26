<?php

namespace ABC\AWSStorage;
class AWSStorage extends \ExternalModules\AbstractExternalModule {

    const SSENC = "AES256";
    const UUID = "14365123651274";
    const ALGORITHM = "AWS4-HMAC-SHA256";
    const ALG = "SHA256";

    function redcap_every_page_top ($project_id) {
        if (isset($_COOKIE['PHPSESSID']) && !empty($_COOKIE['PHPSESSID'])) {
            // This is a workaround for Firefox omitting session cookie in some configurations
            setcookie('PHPSESSID', $_COOKIE['PHPSESSID'], 0,'/', null,true,true);
        } 
        $jsUrl = $this->getUrl("AWSScript.js"); 
        $resultUrl = $this->getUrl("AWSResult.php");
        $signedPolicy = $this->getSignedPolicy($resultUrl);
        $baseDir = $this->getProjectSetting("base-dir");
        print("<script>
            awsstore = {
                'base_dir' : '" . $baseDir . "'
            };
            document.addEventListener('DOMContentLoaded', function() {
            var bodyLoaded = document.body;
            var url = '" . $jsUrl . "'
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = url;
            bodyLoaded.appendChild(script);
            awsstore.origFilePopUp = filePopUp;
            awsstore.origUploadFilePreProcess = uploadFilePreProcess;
            var filePopUp = awsstore.newFilePopUp;
            var uploadFilePreProcess = awsstore.uploadFilePreProcess;
        });</script>");
        print("\n<script>\n");
        print("\tvar AWSS3Bucket ='" . $signedPolicy[0] . "';\n");
        print("\tvar AWSS3Region ='" . $signedPolicy[1] . "';\n");
        print("\tvar AWSS3BucketID ='" . $signedPolicy[2] . "';\n");
        print("\tvar AWSS3DateStamp ='" . $signedPolicy[3] . "';\n");
        print("\tvar AWSS3Redirect ='" . $resultUrl. "';\n");
        print("\tvar AWSS3AmzDate ='" . $signedPolicy[4] . "';\n");
        print("\tvar AWSS3Acl ='" . $signedPolicy[5] ."';\n");
        print("\tvar AWSS3Policy = '" . $signedPolicy[6] . "';\n");
        print("\tvar AWSS3Signiture = '" . $signedPolicy[7] . "';\n");
        print("\tvar ABCFilenames = " . json_encode($signedPolicy[8], JSON_PRETTY_PRINT) . ";\n");
        print("\tvar ABCSites = " . json_encode($signedPolicy[9], JSON_PRETTY_PRINT) . ";\n");
        print("\tvar ABCTimepoints = " . json_encode($signedPolicy[10], JSON_PRETTY_PRINT) . ";\n");
        print("</script>\n");
    }
    public static function signiture($bucketKey, $dateStamp, $regionName, $serviceName) {
        $kDate = hash_hmac(AWSStorage::ALG, $dateStamp, 'AWS4' . $bucketKey, true);
        $kRegion = hash_hmac(AWSStorage::ALG, $regionName, $kDate, true);
        $kService = hash_hmac(AWSStorage::ALG, $serviceName, $kRegion, true);
        $kSigning = hash_hmac(AWSStorage::ALG, 'aws4_request', $kService, true);
        return $kSigning;
    }

    public static function sign($awskey, $msg) {
        return  hash_hmac(AWSStorage::ALG, $msg, $awskey);
    }

    public static function makePolicy($expire, $bucket, $bucketID, $region,
                                      $service, $dateStamp, $nameCond, $acl,
                                      $redirect, $contentCond, $amzdate) {
        $cred = $bucketID . "/" . $dateStamp . "/" . $region . "/" . $service . "/aws4_request";
        $conditions = array(
                        array("bucket"=>$bucket),
                        $nameCond,
                        array("acl"=>$acl),
                        array("success_action_redirect"=>$redirect),
                        $contentCond,
                        array("x-amz-meta-uuid"=>AWSStorage::UUID),
                        array("x-amz-server-side-encryption"=>AWSStorage::SSENC),
                        array("starts-with", "\$x-amz-meta-tag", ""),
                        array("x-amz-credential"=>$cred),
                        array("x-amz-algorithm"=>AWSStorage::ALGORITHM),
                        array("x-amz-date"=>$amzdate));
        $policy = array("expiration"=> $expire, "conditions"=>$conditions);
        return utf8_encode(json_encode($policy, JSON_UNESCAPED_SLASHES));
    }

    public function getSignedPolicy($resultUrl) {
        require_once 'config.php';
        $base = $this->getProjectSetting("base-dir");
        $expire = date("Y-m-d\Th:i:s.000\Z", strtotime('+ 1 week'));
        $dateStamp = date("Ymd");
        $amzdate = date("Ymd\This0\Z");
        $nameCond = array("starts-with", "\$key", $base . "/");
        $contentCond = array("starts-with", "\$Content-Type", "");
        $policy = AWSStorage::makePolicy($expire, $bucket, $bucketID, $region,
                   $service, $dateStamp, $nameCond, $acl,
                   $resultUrl, $contentCond, $amzdate);
        $policy64 = base64_encode($policy);
        $sig = AWSStorage::signiture($accessKey, $dateStamp, $region, $service);
        $signed = AWSStorage::sign($sig, $policy64);
        return array($bucket, $region, $bucketID, $dateStamp, $amzdate, $acl, $policy64, $signed, $filenames, $sites, $timepoints);
  }
    
}
?>
