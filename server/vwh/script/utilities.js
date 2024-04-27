
function display(true_false, elements) {
	for (let index = 0; index < elements.length; index++) {
		elements[index].style.display = true_false ? '' : 'none';
	}
}

function dialog_create(message) {
	let dialog = document.body.appendChild(document.createElement('dialog'));

	let content = dialog.appendChild(document.createElement('div'));
	content.classList.add('dialog_content');

	content.appendChild(document.createElement('p')).innerHTML = message;

	return dialog;
}

function dialog_create_closable(message) {
	let dialog = document.body.appendChild(document.createElement('dialog'));

	let content = dialog.appendChild(document.createElement('div'));
	content.classList.add('dialog_content');

	content.appendChild(document.createElement('p')).innerHTML = message;

	let close_button = document.createElement('button');
	close_button.innerHTML = 'Continue';
	close_button.addEventListener('click', () => {
		dialog.close();
	});

	content.appendChild(close_button);

	return dialog;
}
