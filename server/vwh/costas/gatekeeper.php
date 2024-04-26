<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new AssemblerOperate('Gatekeeper');

$a->operator_ensure(Operator::Gatekeeper);

$a->assemble();

?>

<script src="/script/short_polling.js"></script>
<script>
	short_polling(3 /* seconds */, /* for */ 'gatekeeper', /* to retrieve */ (data) => {
		console.log(data); // TODO use 'data' to update like secretary
	});
</script>
