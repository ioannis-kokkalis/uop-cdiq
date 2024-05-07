
const form = document.getElementById('form');
const iwer_form = document.getElementById('iwer_form');

const iwee_filter = document.getElementById('iwee_filter');
const iwee_select = document.getElementById('iwee_select');
const iwee_buttons = document.getElementById('iwee_buttons');
const iwee_notice = document.getElementById('iwee_notice');

const iwer_fieldset = document.getElementById('iwer_fieldset');
const iwer_filter = document.getElementById('iwer_filter');
const iwer_checkboxes = document.getElementById('iwer_checkboxes');
const iwer_button_add = document.getElementById('iwer_button_add');
const iwer_info_dialog = document.getElementById('iwer_info_dialog');
const iwer_info_dialog_delete = document.getElementById('iwer_info_dialog_delete');
const iwer_info_dialog_id = document.getElementById('iwer_info_dialog_id');

// TODO maybe validate that all elements needed exist? then move all document.get... up here

// elements.forEach(element => {
// 	if(element === null) {
// 		throw new Error("Can't retrive all elements.");
// 	}
// });

display( true, [iwee_filter, iwee_select, iwer_fieldset]);
display(false, [iwee_buttons, iwee_notice]);

// ...

const iwee_option_empty = iwee_select.appendChild(document.createElement('option'));
iwee_option_empty.text = 'Select an Interviewee';
iwee_option_empty.selected = true;
iwee_option_empty.value = null;

const interviewees_array = [];
const interviewers = {};

// ...

iwee_filter.addEventListener('input', function () {

	iwee_filter.value = iwee_filter.value.trim();

	if (this.value === '') {
		display( true, [iwee_select, iwer_fieldset]);
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
		display(false, [iwee_select, iwer_fieldset]);

		iwee_notice.innerText = 'An Interviewee with email "' + this.value + '" will be created, there is not an existing one.';
		iwee_option_empty.selected = true;
	}
	else {
		display( true, [iwee_select, iwer_fieldset]);
		display(false, [iwee_notice]);

		options_on[0].selected = true;
	}

	iwee_select.dispatchEvent(new Event('change'));
});

iwer_filter.addEventListener('input', function () {
	// TODO
});

iwee_select.addEventListener('change', function () {
	display(this.options[this.selectedIndex] !== iwee_option_empty, [iwee_buttons]);
});

iwer_button_add.addEventListener('click', function () {
	display(false, [iwer_info_dialog_delete]);
	iwer_info_dialog.showModal();
});

// ...

const dialog_processing = dialog_create("Processing...");
const dialog_success = dialog_create_closable("Success :3");

// ...

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

form.addEventListener("submit", function (event) {
	confirm_message = (() => { // TODO rework to be button based
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
				ret_value = 'CREATING an interviewee with email "' + iwee_filter.value + '".';
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

	submiting(form, confirm_message, null, event);
});

iwer_form.addEventListener("submit", function(event) {
	let button = event.submitter;

	if(button === document.getElementById('iwer_info_dialog_abort')) {
		iwer_info_dialog.close();
		iwer_form.reset();

		return;
	}

	let button_confirm = document.getElementById('iwer_info_dialog_confirm');
	let button_delete = document.getElementById('iwer_info_dialog_delete');

	confirm_message = (() => {
		if(button === button_confirm && iwer_info_dialog_id.value === 'null') {
			return '// TODO confirmation message of what ADDING';
		}
		if(button === button_confirm /* && iwer_info_dialog_id.value !== 'null' */) {
			return '// TODO confirmation message of what EDITING';
		}
		else if(button === button_delete) {
			return '// TODO confirmation message of what DELETING';
		}
		return null;
	})();

	submiting(iwer_form, confirm_message, () => {
		iwer_info_dialog.close();
		iwer_form.reset();
	}, event);
});

// ...

function update(data) {

	// TODO MUST remember selection do not remove all and recreate them
	// otherwise on each update selection is lost
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

	let interviewer_ids_to_delete = Object.keys(interviewers);

	data['interviewer'].forEach(function (iwer_row) {

		let interviewer = interviewers[iwer_row['id']];

		if(interviewer === undefined) {

			interviewer = interviewers[iwer_row['id']] = {
				'id': iwer_row['id'],

				'element_label': document.createElement('label'),
				'element_input': document.createElement('input'),
				'element_img': document.createElement('img'),
				'element_p': document.createElement('p'),
			}

			interviewer['element_label'].append(
				interviewer['element_input'],
				interviewer['element_img'],
				interviewer['element_p'],
			);

			interviewer['element_label'].htmlFor =
			interviewer['element_input'].value =
			interviewer['element_input'].id =
			interviewer['id'];

			interviewer['element_input'].type = "checkbox";
			interviewer['element_input'].name = "interviewers[]";
			interviewer['element_input'].addEventListener('click', function(event) {
				if(iwee_option_empty.selected === true) {
					event.preventDefault();

					iwer_info_dialog_id.value = interviewer['id'];
					document.getElementById('iwer_info_dialog_name').value = interviewer['name'];
					document.getElementById('iwer_info_dialog_table').value = interviewer['table'];
					document.getElementById('iwer_info_dialog_jobs').value = interviewer['jobs'];

					iwer_button_add.dispatchEvent(new Event("click"));
					
					display(true, [iwer_info_dialog_delete]);
				}
			});

			iwer_checkboxes.appendChild(interviewer['element_label']);
		}
		else {
			interviewer_ids_to_delete.splice(interviewer_ids_to_delete.indexOf(interviewer['id'].toString()), 1);
		}
	
		// update fields even tho they may no need to be updated
		// backend doesn't say what changed, just sends all each time

		interviewer['name'] = iwer_row['name'];
		interviewer['table'] = iwer_row['table_number'] === '' ? '-' : iwer_row['table_number'];
		interviewer['image'] = iwer_row['image_resource_url'];
		interviewer['jobs'] = iwer_row['jobs'];
		interviewer['active'] = iwer_row['active'];
		interviewer['available'] = iwer_row['available'];

		interviewer['element_img'].src = interviewer['image'];
		interviewer['element_p'].innerHTML =
			interviewer['name'] + "<br>Table: " + interviewer['table'];
	});

	interviewer_ids_to_delete.forEach(function (id) {
		interviewers[id]['element_input'].name = '';
		iwer_checkboxes.removeChild(interviewers[id]['element_label']);
		delete interviewers[id];
	});

	// ===
}
