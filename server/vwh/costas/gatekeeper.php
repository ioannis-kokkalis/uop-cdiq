<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new AssemblerOperate('Gatekeeper');

$a->operator_ensure(Operator::Gatekeeper);

$a->body_main = function() { ?>

	<dialog id="dialog-asd9uih" class="info-dialog">
		<p>Click on interviewers to handle their current interview.</p>
		<p>Pay attention when an interview has gone to Decision, and also when on Calling or Happening.</p>
		<p>Your possible actions are:</p>
		<ul>
			<li><strong>Happening</strong>: when an interview is on Calling or Decision will move the interview to Happening, aka the interviewee arrives and goes on the interview.</li>
			<li><strong>Completed</strong>: the interview is completed successfully and the interviewee is leaving.</li>
			<li><strong>Dequeue</strong>: removes the interview. The related interviewee can enqueue again on that interviewer via Secretary.</li>
			<li><strong>Pausing</strong>: will move the interviewer from whatever state (Avaliable, Calling, Decision, Happening) to Unavailable like it never started calling any interviewee. At any state except Avaliable, the related interviewee will go back in queue and we will pretend the Calling never happened in the first place. Interviewer will no longer be able to call next interviewee until unpaused.</li>
		</ul>

		<button onclick="document.getElementById('dialog-asd9uih').close();">Thanks!</button>
	</dialog>

	<div id="as8u9dji" class="horizontal_buttons">
		<button id="9a8sdfuh" onclick="document.getElementById('dialog-asd9uih').showModal();
			document.getElementById('dialog-asd9uih').scrollTo(0,0);"
		>Information</button>
		<button onclick="document.getElementById('as8u9dji').style.display = 'none';">Hide</button>
	</div>

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
