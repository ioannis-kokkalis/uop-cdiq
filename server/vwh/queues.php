<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new Assembler('Interviews');

$a->body_main = function() { ?>

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
