if ( typeof(filePopUp)  !== 'undefined') {
    awsstore.origFilePopUp = filePopUp;
    awsstore.origUploadFilePreProcess = uploadFilePreProcess;
}

awsstore.newFilePopUp = function (fieldName, value) {
    awsstore.origFilePopUp(fieldName, value);
    if(fieldName.startsWith('aws')) {
        field = document.getElementById('field_name');
        if(field && field.value.startsWith('aws')) {
            form = field.parentNode;
            form.getElementsByTagName("span")[0].textContent = "(Max file size: 5 GB)";
        }
    }
}

//Redefine uploadFilePreProcess
awsstore.uploadFilePreProcess = function() {
    var field = document.getElementById('field_name');
    if(field.value.startsWith('aws')) {
        var field_name = field.value.substring(0,field.value.length-8)
        //var log_name = "log_" + field.value.substring(0,field.value.length-8);
        //var log_field = document.getElementsByName(log_name)[0];
        var form = field.parentNode; 
        inputs = form.getElementsByTagName("input");
        var addEl = inputs[0].name == "myfile";
        var file_name = "";
        var isSignature = false;
        for(i = 0; i < inputs.length; i++) {
            if(inputs[i].name == "file" || inputs[i].name == "myfile") {
                file_name = inputs[i].value;
                var names = file_name.split("\\");
                var name = names[names.length-1];
                //Do not check the name
                //Log after upload
		//log_field.value = name;
            }
            else if(inputs[i] == "myfile_base64") {
                isSignature = inputs[i].value.length > 0;
            }
        }
        var missingFile = !isSignature && file_name.length == 0;
        if (!isSignature && missingFile) {
            simpleDialog("You must first choose a file to upload","ERROR",null,300);
            return false;
        } else {
            //Create the form inputs for AWS
            var subdir = field_name.split('_')[1];
            var urlParam = new URLSearchParams(window.location.search);
	    var pageDir = (new URLSearchParams(window.location.search)).get('page').replaceAll("_", " ");

            var dir = awsstore.base_dir + "/" + subdir + "/" + pageDir;
            inputs[0].name = "file";
            inParent = inputs[0].parentNode;
            names = ["key", "acl", "success_action_redirect", "Content-Type", "x-amz-meta-uuid", "x-amz-server-side-encryption", "x-amz-credential", "x-amz-algorithm", "x-amz-date", "x-amz-meta-tag", "policy", "x-amz-signature"]
            values = [dir + "/${filename}", AWSS3Acl, AWSS3Redirect, "", "14365123651274", "AES256", AWSS3BucketID + "/" + AWSS3DateStamp + "/" + AWSS3Region + "/s3/aws4_request", "AWS4-HMAC-SHA256", AWSS3AmzDate, "", AWSS3Policy,  AWSS3Signiture ];
            first = form.childNodes[0];
            for(i = 0; i < names.length; i++) {
                el = document.createElement("input");
                el.name = names[i];
                el.type = "hidden";
                el.setAttribute("value",values[i]);
                form.insertBefore(el,first);
            }
            form.action = "https://" + AWSS3Bucket + ".s3.amazonaws.com/";
            form.method = "post";
            form.enctype = "multipart/form-data";
            form.submit();
            return;
        }
    } else {
        return awsstore.origUploadFilePreProcess();
    }
}

filePopUp = awsstore.newFilePopUp;
uploadFilePreProcess = awsstore.uploadFilePreProcess;

awsstore.AWSStopUpload = function(success,this_field,doc_id,doc_name,study_id,doc_size,event_id,download_page,delete_page,doc_id_hash,instance) {
	var result = '';
	if (success == 1){
	    log = document.getElementsByName("log_" + this_field)[0];
	    log.value = doc_name;
	    try {
            if (typeof window.parent.lang_remove_file != 'undefined') {
                var lang_remove_file = window.parent.lang_remove_file;
                var lang_send_it = window.parent.lang_send_it;
                var lang_upload_new_version = window.parent.lang_upload_new_version;
            }
        } catch (e) { }
        if (typeof lang_remove_file == 'undefined') {
            var lang_remove_file = 'Remove file';
            var lang_send_it = 'Send-It';
            var lang_upload_new_version = 'Upload new version';
        }
        var sigimg = $('#'+this_field+'-sigimg');
		result = '<div style="font-weight:bold;font-size:14px;text-align:center;color:green;"><br><i class="fas fa-check"></i> File was successfully uploaded!<\/div>';
		document.getElementById(this_field+"-link").style.display = 'block';
		doc_name = truncate_filename(doc_name, 34);
		document.getElementById(this_field+"-link").innerHTML = doc_name+doc_size;
		document.getElementById(this_field+"-link").href = "";
		$('#'+this_field+"-link").attr('onclick', "");
		var newlinktext = '<a href="javascript:;" style="font-size:10px;color:#C00000;" onclick=\'deleteDocumentConfirm('+doc_id+',"'+this_field+'","'+study_id+'",'+event_id+','+instance+',"'+delete_page+'&__response_hash__="+$("#form :input[name=__response_hash__]").val());return false;\'><i class="far fa-trash-alt mr-1"></i>'+lang_remove_file+'</a>';
    }
	document.getElementById('f1_upload_form').style.display = 'block';
	document.getElementById('f1_upload_form').innerHTML = result;
	document.getElementById('f1_upload_process').style.display = 'none';
	// Close dialog automatically with fade effect
	if ($("#file_upload").hasClass('ui-dialog-content')) {
		if (success == 1) {
			// If this is a signature field, then close dialog immediately
			if ($('#'+this_field+'-sigimg').length) {
				$('#file_upload').dialog('destroy');
				if (inIframe()) {
					var urlparts = window.location.href.split('#');
					window.location.href = urlparts[0]+'#'+this_field+'-tr';
				}
			} else {
				$('#file_upload').dialog('option', 'buttons', { "Close": function() { $(this).dialog("destroy"); } });
				setTimeout(function(){
					if ($("#file_upload").hasClass('ui-dialog-content')) $('#file_upload').dialog('option', 'hide', {effect:'fade', duration: 200}).dialog('close');
					// Destroy the dialog so that fade effect doesn't persist if reopened
					setTimeout(function(){
						if ($("#file_upload").hasClass('ui-dialog-content')) $('#file_upload').dialog('destroy');
					},200);
					if (inIframe()) {
						var urlparts = window.location.href.split('#');
						window.location.href = urlparts[0]+'#'+this_field+'-tr';
					}
				},1500);
			}
		} else {
			$('#file_upload').dialog('option', 'buttons', { "Close": function() { $(this).dialog("destroy"); },
				"Try again": function() { $('#file_upload').dialog('destroy'); $('#'+this_field+'-linknew a.fileuploadlink').trigger('click'); } });
		}
	}
	// Trigger branching logic in case a "file" field is involved in branching
	calculate(this_field);
	doBranching(this_field);
	return true;
}
