$(document).ready(function() {
	$('input[type=file]').bootstrapFileInput();
	$('.file-inputs').bootstrapFileInput();
	
	$("#submit_form").ajaxForm({
		url: "/submissions/post",
		dataType: 'json',
		beforeSerialize: function() {
			console.log($("#sourceFileSelector").attr("files"));
			if (!$("#sourceFileSelector").val()) {
				$("#action_feedback").html("<span class=\"text-danger\">Please choose a file to submit.</span>");
				return false;
			}/* else if (file_dom.attr("files")[0].size > 102400){
				prompt_dom.html("<span class=\"text-warning\">File must be an image of size no more than 100KiB.</span>").removeClass("hidden");
				return false;
			}*/
		},
		beforeSend: function() {
			$("#action_feedback").html("(Uploading...)");
		},
		uploadProgress: function(event, position, total, percentComplete) {
			var percentVal = percentComplete + '%';
			$("#action_feedback").html("(Uploading " + percentVal + ")");
		},
		success: function() {
			$("#action_feedback").html("(Uploaded.)");
		},
		complete: function(xhr) {
			if (xhr.responseJSON.error == "success") {
				$("#action_feedback").html("<span class=\"text-success\">Successfully submitted the file.</span>");
				if (xhr.responseJSON.more_status == "error")
					$("#action_feedback").append("<p class=\"text-warning\">However, system failed to add the grading task to queue; will retry later.</p>");
				$("#history_body").prepend(xhr.responseJSON.new_record_data);
			} else {
				$("#action_feedback").html("<span class=\"text-danger\">" + xhr.responseJSON.error_description + "</span>");
			}
		}
	});
	
	if ($("#forgot_password_form").length) {
		$("#forgot_password_form").ajaxForm({
			dataType: 'json',
			beforeSubmit: function(formData, jqForm) {
				// no need to check if user_id is empty since it is required.
				$("#help_text").html("<p class=\"text-info\">Requesting...</p>")
			},
			complete: function(xhr) {
				if (xhr.status == 200) {
					if (xhr.responseJSON.error) {
						$("#help_text").html("<p class=\"text-danger\">" + xhr.responseJSON.error_description + " (" + xhr.responseJSON.error + ")</p>");
					} else {
						$("#help_text").html("<p class=\"text-success\">" + xhr.responseJSON.message + "</p>");
					}
				} else {
					$("#help_text").html("<p class=\"text-danger\">An error occurred performing the request. If it persists please contact admin.</p>");
				}
			}
		});
	}
	
});
