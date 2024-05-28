<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new Assembler('Interviews');

$a->body_main = function() { ?>

	<dialog id="dialog-asd9uih" class="info-dialog">
		<p>Here you can see each interviewer with their current interview state.</p>
		<p>Possible states are:</p>
			<ul>
				<li><span class='av'>Available</span>: either no interviewee is enqueued or those enqueued are on another interview at the moment.</li>
				<li><span class='ca'>Calling</span>: the shown interviewee can go/start the interview via the Gatekeeper.</li>
				<li><span class='de'>Decision</span>: calling period ended, Gatekeeper will decide if the interviewee arrived or not, if you are the interviewee find the Gatekeeper, you are late.</li>
				<li><span class='ha'>Happening</span>: the interviewee arrived in time and the interview has already started.</li>
				<li><span class='pa'>Paused</span>: the interviewer is not available to attend any interviews at the moment.</li>
			</ul>

		<hr>

		<p>Click any interviewer to see the interviewees enqueued for an interview with that interviewer.</p>
		<p>When there is an interview in state Calling or Decision or Happening, the corresponding interviewee will match the interview state color.</p>
		<p>Any other interviewee enqueued will be either:
		<ul>
			<li><span class='av'>Available</span>: when <strong>not</strong> Unavailable.</li>
			<li><span class='pa'>Unavailable</span>: when it is in another interview at any state Calling or Decision or Happening, or the interviewee got paused via the Secretary.</li>
		</ul>
		<p>When the system selects the next interviewee to start Calling, always <strong>the most left and available</strong> one will be selected.</p>
		
		<hr>
		
		<p>You don't need to refresh for new changes.</p>

		<hr>

		<button onclick="document.getElementById('dialog-asd9uih').close();">Thanks!</button>
	</dialog>

	<div id="as8u9dji" class="horizontal_buttons">
		<button id="9a8sdfuh" onclick="document.getElementById('dialog-asd9uih').showModal();
			document.getElementById('dialog-asd9uih').scrollTo(0,0);"
		>What is this place?</button>
		<button onclick="document.getElementById('as8u9dji').style.display = 'none';">Hide</button>
	</div>

	<div id="container_interviewers" class="container_interviewers">
		<p id="no_interviewers_message">No Interviewers in the system.</p>
	</div>

	<script src="/script/utilities.js"></script>
	<script src="/script/short_polling.js"></script>
	<script src="/script/queues.js"></script>
	<script>
		// TODO move it back to 5 seconds
		short_polling(2 /* seconds */, /* for */ 'queues', /* to retrieve */ (data) => {
			update(data);
		});
	</script>
<?php };

$a->assemble();
