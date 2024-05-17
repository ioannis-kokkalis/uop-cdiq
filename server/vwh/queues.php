<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new Assembler('Interviews');

$a->body_main = function() { ?>

	<p>
	<?php # TODO placeholder, replace when actually retrieving data
		$no_data_yet_message = array(
			"Still waiting for data...",
			"Awaiting data to be retrieved...",
			"Fetching data...",
		);

		echo $no_data_yet_message[array_rand($no_data_yet_message)];
	?>
	</p>

	<script src="/script/utilities.js"></script>
	<script src="/script/short_polling.js"></script>
	<script>
		short_polling(5 /* seconds */, /* for */ 'queues', /* to retrieve */ (data) => {
			console.log(data); // TODO use 'data' array and update the UI
		});
	</script>
<?php };

$a->assemble();
