
const form = document.getElementById('form');

const iwee_filter = document.getElementById('iwee_filter');
const iwee_select = document.getElementById('iwee_select');
const iwee_buttons = document.getElementById('iwee_buttons');
const iwee_notice = document.getElementById('iwee_notice');

// TODO maybe validate that all elements needed exist? then move all document.get... up here

// elements.forEach(element => {
// 	if(element === null) {
// 		throw new Error("Can't retrive all elements.");
// 	}
// });

display( true, [iwee_filter, iwee_select]);
display(false, [iwee_buttons, iwee_notice]);

// ...

const iwee_option_empty = iwee_select.appendChild(document.createElement('option'));
iwee_option_empty.text = 'Select an Interviewee';
iwee_option_empty.selected = true;
iwee_option_empty.value = null;

const interviewees_array = [];
const interviewers_array = [];

// ...

iwee_filter.addEventListener('input', function () {

	iwee_filter.value = iwee_filter.value.trim();

	if (this.value === '') {
		display( true, [iwee_select]);
		display(false, [iwee_notice]);

		display(true, iwee_select.options);
		
		iwee_option_empty.selected = true;
		iwee_select.dispatchEvent(new Event('change'));

		return;
	}

	// else the filter has contents

	let options_on  = [];
	let options_off = [iwee_option_empty];

	for (let i = 0; i < iwee_select.options.length; i++) {
		if(i == iwee_option_empty.index) {
			continue;
		}
		
		const oi = iwee_select.options[i];
		
		if (oi.text.indexOf(this.value) === -1) {
			options_off.push(oi);
		}
		else {
			options_on.push(oi);
		}
	}

	display( true, options_on);
	display(false, options_off);

	if (options_on.length === 0) {
		display( true, [iwee_notice]);
		display(false, [iwee_select]);

		iwee_notice.innerText = 'An Interviewee with email "' + this.value + '" will be created, there is not an existing one.';
		iwee_option_empty.selected = true;
	}
	else {
		display( true, [iwee_select]);
		display(false, [iwee_notice]);

		options_on[0].selected = true;
	}

	iwee_select.dispatchEvent(new Event('change'));
});

iwee_select.addEventListener('change', function () {
	display(this.options[this.selectedIndex] !== iwee_option_empty, [iwee_buttons]);
});

const dialog_processing = dialog_create("Processing...");
const dialog_success = dialog_create_closable("Success :3");

form.addEventListener("submit", function (event) {
	const update_id_when_pressing_submit = update_id

	event.preventDefault();

	confirm_message = (() => {
		let ret_value = null;
		
		let option_selected = iwee_select.options[iwee_select.selectedIndex];
		
		if(option_selected === undefined) {
			return ret_value;
		}
		
		let button = event.submitter;
		
		let button_active_inactive = document.getElementById('iwee_button_active_inactive');
		let button_delete = document.getElementById('iwee_button_delete');
		let button_update = document.getElementById('form_button_update');
		
		if([button_active_inactive, button_delete, button_update].indexOf(button) === -1) {
			return ret_value;
		}

		if(option_selected === iwee_option_empty) {
			if(button === button_update && iwee_filter.value !== '') {
				ret_value = 'CREATING an interviewee as "' + iwee_filter.value + '" and then UPDATING.';
			}
		}
		else { // if(option_selected !== iwee_option_empty) => some interviewee
			let interviewee_selected = interviewees_array[option_selected.value];

			if(button === button_update) {
				ret_value = 'UPDATING';
			}
			else if(button === button_active_inactive) {
				ret_value = "marking as " + (interviewee_selected.active ? "ACTIVE" : "INACTIVE");
			}
			else if(button === button_delete) {
				ret_value = 'DELETING';
			}
			else {
				ret_value = 'UNKNOWN ACTION with';
			}

			ret_value += ' interviewee with ID:' + interviewee_selected.id + ' and EMAIL:' + interviewee_selected.email;
		}

		return ret_value;
	})();

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
});

// ...

function update(data) {

	// TODO consider remembering selection? do not remove all and recreate them
	// based on data: update existing ones, remove non present, add new ones

	for (i = iwee_select.length - 1; i >= 0; i--) {
		if (iwee_select[i] === iwee_option_empty) {
			continue;
		}
		iwee_select.remove(i);
	}
	
	data['interviewee'].forEach(function (iwee_row) { 

		let interviewee = interviewees_array[iwee_row['id']] = {
			'id': iwee_row['id'],
			'email': iwee_row['email'],
			'active': iwee_row['active'],
			'available': iwee_row['available'],
			'element_option': document.createElement('option')
		};
		
		interviewee['element_option'].value = iwee_row['id'];
		interviewee['element_option'].text = iwee_row['id'] + ' | ' + interviewee['email'];
		
		iwee_select.appendChild(interviewee['element_option']);

	});

	iwee_select.dispatchEvent(new Event('change'));
	iwee_filter.dispatchEvent(new Event('input'));

	// ===

	// TODO similar for let iwer_rows = data['interviewer'];

	// data['interviewer'].forEach(function (iwer_row) {
	// // <div>
	// // 	<input type="checkbox">
	// // 	<div>test</div>
	// // </div>
	// });
}
