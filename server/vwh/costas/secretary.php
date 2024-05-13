<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new AssemblerOperate('Secretary');

$a->operator_ensure(Operator::Secretary);

// ---

$a->body_main = function() { ?>

	<!-- TODO dialog with HELP information like
		to ensure order of interviews
		submit one at a time
	-->

	<form id="form"> <!-- submitting with JavaScript XMLHttpRequest -->
		<!--  TODO (haha) maybe consider static form submission in case JS is disabled -->

		<button type="submit"
			onclick="
				event.preventDefault();
				document.getElementById('form_button_update').click();
				return;
			"
		hidden></button>

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

			<p id="iwee_notice" style="text-align: center;"></p>
		</fieldset> <!-- interviewee -->

		<fieldset id="iwer_fieldset"> <!-- interviewer -->
			<legend>Interviewer(s)</legend>

			<input id="iwer_filter" type="text" placeholder="Filter interviewers... (not implemented yet)">

			<div id="iwer_checkboxes">
				<!-- filled in script -->
			</div>

			<div id="iwer_buttons" class="horizontal_buttons">
				<button id="iwer_button_add" name="iwer_button_add" type="button">Add</button>
			</div>
		</fieldset> <!-- interviewer -->

		<button id="form_button_update" name="form_button_update" type="submit">Update</button>

	</form>

	<dialog id="iwer_info_dialog">
		<form id="iwer_form" method="dialog">
			
			<button type="submit"
				onclick="
					event.preventDefault();
					document.getElementById('iwer_info_dialog_confirm').click();
					return;
				"
			hidden></button>

			<input type="text" id="iwer_info_dialog_id" name="iwer_info_dialog_id" value="null" hidden>
			
			<label for="iwer_info_dialog_name">Name:
				<input type="text" id="iwer_info_dialog_name" name="iwer_info_dialog_name" required>
			</label>

			<label for="iwer_info_dialog_table">Table:
				<input type="text" id="iwer_info_dialog_table" name="iwer_info_dialog_table">
			</label>

			<label for="iwer_info_dialog_image">Image:
				<input type="file" id="iwer_info_dialog_image" name="iwer_info_dialog_image" accept="image/*">
			</label>

			<label for="iwer_info_dialog_jobs">Jobs (seperated at newline):
				<textarea id="iwer_info_dialog_jobs" name="iwer_info_dialog_jobs" rows=7 style="resize: none;  text-wrap: nowrap;"></textarea>
			</label>

			<div class="horizontal_buttons">
				<input type="submit" id="iwer_info_dialog_delete" name="iwer_info_dialog_delete" value="Delete" formnovalidate>
				<input type="submit" id="iwer_info_dialog_abort" name="iwer_info_dialog_abort" value="Abort" formnovalidate autofocus>
				<input type="submit" id="iwer_info_dialog_confirm" name="iwer_info_dialog_confirm" value="Confirm">
			</div>
		</form>
	</dialog>

<?php };

$a->assemble();

?>

<script src="/script/utilities.js"></script>
<script src="/script/short_polling.js"></script>
<script src="/script/submit.js"></script>
<script src="/script/secretary.js"></script>
<script>
	short_polling(2 /* seconds */, /* for */ 'secretary', /* to retrieve */ (data) => {
		update(data);
	});
</script>
