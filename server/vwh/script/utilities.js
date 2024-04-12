
function display(true_false, elements) {
	for (let index = 0; index < elements.length; index++) {
		elements[index].style.display = true_false ? '' : 'none';
	}
}
