<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new AssemblerOperate('Secretary');

$a->operator_ensure(Operator::Secretary);

// ---

$a->body_main = function() { ?>

	<form id="form"> <!-- submitting with JavaScript XMLHttpRequest -->
		<!--  TODO (haha) maybe consider static form submission in case JS is disabled -->
		
		<fieldset id="iwee"> <!-- interviewee -->
			<legend>Interviewee</legend>

			<input id="iwee_filter" name="iwee_filter" type="text" placeholder="Filter interviewees...">
			<select id="iwee_select" name="iwee_select">
				<!-- filled in scripts -->
			</select>

			<div id="iwee_buttons" class="horizontal_buttons">
				<button id="iwee_button_active_inactive" name="iwee_button_active_inactive" type="submit">Active / Inactive</button>
				<button id="iwee_button_delete" name="iwee_button_delete" type="submit">Delete</button>
			</div>

			<p id="iwee_notice" align="center"></p>
		</fieldset> <!-- interviewee -->
		
		<?php
		// echo '
		// <fieldset id="iwer"> <!-- interviewer -->
		// 	<legend>Interviewer(s)</legend>

		// 	<input id="iwer_filter" type="text" placeholder="Filter interviewers...">

		// 	<div id="iwer_checkboxes">
		// 		<!-- filled in script -->
		// 	</div>

		// 	<div id="iwer_buttons" class="horizontal_buttons">
		// 		<button id="iwer_button_add" name="iwer_button_add" type="submit">Add</button>
		// 	</div>
		// </fieldset> <!-- interviewer -->
		// ';
		?>

		<button id="form_button_update" name="form_button_update" type="submit">Update</button>

	</form>

<?php };

$a->assemble();

?>

<script src="/script/utilities.js"></script>
<script src="/script/short_polling.js"></script>
<script src="/script/secretary.js"></script>
<script>
	short_polling(2 /* seconds */, /* for */ 'secretary', /* to retrieve */ (data) => {
		update(data);
	});
</script>
