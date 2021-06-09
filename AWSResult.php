<?php
    print("
    <script language='javascript' type='text/javascript'>
    window.parent.dataEntryFormValuesChanged = true;
    window.parent.window.awsstore.AWSStopUpload(true, window.frameElement.parentElement.elements['field_name'].value.split('-linknew')[0], -1, '', -1, -1, -1, '', '', 0, '');
   </script>");
?>
