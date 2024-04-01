<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new Assembler('Interview Queues Currently');

$a->body_main = function() { ?>
	<p>Ask database for the queues.</p>
<?php };

$a->assemble();
