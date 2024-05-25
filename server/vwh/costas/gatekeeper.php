<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new AssemblerOperate('Gatekeeper');

$a->operator_ensure(Operator::Gatekeeper);

$a->body_main = function() { ?>
	<div id="container_interviewers" class="container_interviewers">
		<p id="no_interviewers_message">No Interviewers in the system.</p>
	</div>
	
	<dialog id="dialog_action">
		<form id="dialog_action_form" method="dialog">
			<input id="input_interview_id" name="input_interview_id" type="text" value="null" hidden>
			<input id="input_interviewer_id" name="input_interviewer_id" type="text" value="null" hidden>

			<button id="button_to_happening" name="button_to_happening" type="sumbit">Happening</button>
			<button id="button_to_completed" name="button_to_completed" type="sumbit">Complete</button>
			<button id="button_to_dequeue" name="button_to_dequeue" type="sumbit">Dequeue</button>
			<hr>
			<button id="button_active_inactive" name="button_active_inactive" type="sumbit">Active / Inactive</button>
			<hr>
			<button id="button_cancel" name="button_cancel" type="sumbit" autofocus>Cancel</button>
		</form>
	</dialog>

<?php };

$a->assemble();

?>
<script src="/script/utilities.js"></script>
<script src="/script/short_polling.js"></script>
<script src="/script/submit.js"></script>
<script src="/script/gatekeeper.js"></script>
<script>
	short_polling(2 /* seconds */, /* for */ 'gatekeeper', /* to retrieve */ (data) => {
		update(data);
	});
</script>
