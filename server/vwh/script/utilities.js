
function display(true_false, elements) {
	for (let index = 0; index < elements.length; index++) {
		elements[index].style.display = true_false ? '' : 'none';
	}
}

function dialog_create(message) {
	let dialog = document.body.appendChild(document.createElement('dialog'));

	dialog.appendChild(document.createElement('p')).innerHTML = message;

	return dialog;
}

function dialog_create_closable(message) {
	let dialog = dialog_create(message);

	let close_button = document.createElement('button');
	close_button.innerHTML = 'Continue';
	close_button.addEventListener('click', () => {
		dialog.close();
	});

	dialog.appendChild(close_button);

	return dialog;
}

// ---

function qr_generate(url, img) {
	img.src = 'https://api.qrserver.com/v1/create-qr-code/?data=' + encodeURIComponent(url);
}

function copy_to_clipboard(text) {
	navigator.clipboard.writeText(text)
		.then(
			function() {
				alert("Copied!");
			},
			function() {
				alert("Can't Copy!");
			}
		);
}
