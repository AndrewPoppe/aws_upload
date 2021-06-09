<?php
    $key = $_GET["key"];
    print "
    <script language='javascript' type='text/javascript'>
    window.parent.dataEntryFormValuesChanged = true;
    var success = true;
    var frame = window.frameElement;
    var form = frame.parentElement;
    var this_field = form.elements['field_name'].value.split('-linknew')[0];
    var doc_id = -1;
    var doc_name = '" . $key . "';
    var study_id = -1;
    var doc_size = -1;
    var event_id = -1;
    var download_page = '';
    var delete_page = '';
    var doc_id_hash = 0;
    var instance = '';
    window.parent.window.awsstore.AWSStopUpload(success, this_field, doc_id, doc_name, study_id, doc_size, event_id, download_page, delete_page, doc_id_hash, instance);
   </script>";
?>

