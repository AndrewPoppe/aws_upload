<?php
namespace ABC\AWSStorage;

/* configuration for AWS S3 bucket */
$module = new AWSStorage(); 
$bucket = $module->getProjectSetting("aws-s3-bucket");
$bucketID = $module->getProjectSetting("aws-s3-bucket-id");
$accessKey = $module->getProjectSetting("aws-s3-bucket-key");
$region = $module->getProjectSetting("aws-s3-bucket-region");
$service = 's3';
$alg = 'sha256';
$acl =  $module->getProjectSetting("aws-s3-bucket-acl");
$filenames = array("eeg-zip"=>"zip",
                    "et-dvd"=>"7z",
                    "et-flog"=>"7z",
                    "ndar-eeg-bio"=>"mat",
                    "ndar-eeg-faces"=>"mat",
                    "ndar-eeg-rest"=>"mat",
                    "ndar-eeg-vep"=>"zip",
                    "ndar-et"=>"txt");
$sites = array("BCH"=>"BCH", 140=>"BCH",
                "DUKE"=>"DUKE", 141=>"DUKE",
                "UCLA"=>"UCLA", 142=>"UCLA",
                "UWASH"=>"UWASH", 143=>"UWASH",
                "YALE"=>"YALE", 144=>"YALE");
$timepoints = array("t1", "t2", "t3", "t4");
?>
