
const dialog_processing = dialog_create("Processing...");
const dialog_success = dialog_create_closable("Success :3");

function submiting(form, confirm_message, on_success, event) {
	const update_id_when_pressing_submit = update_id

	event.preventDefault();

	if(confirm_message !== null) {
		if (confirm("You will be: " + confirm_message) === true) {
			dialog_processing.showModal();

			const request = new XMLHttpRequest();

			request.onreadystatechange = function () {
				if (request.readyState === XMLHttpRequest.DONE) {
					
					if (request.status === 200) {
						const response = request.responseText.trim();
						
						if( response === 'ok' ) { 
							dialog_success.showModal();
							if(typeof(on_success) === 'function') {
								on_success();
							}
						}
						else {
							dialog_create_closable('Failure, reason: ' + response).showModal();
							// TODO maybe make the dialog message updatable instead of creating new dialog each time
						}
					}
					else {
						dialog_create_closable('Server error! Status: ' + request.status).showModal();
					}

					dialog_processing.close();
				}
			};

			request.open('POST', '/_update.php?want_to_make_changes=' + update_id_when_pressing_submit, true);
			request.send(new FormData(form, event.submitter));

			return;
		}
	}
	else {
		alert("Nothing happens, no valid conbination of inputs and buttons detected.")
	}
}
