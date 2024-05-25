<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new AssemblerOperate('Authorize');

if(isset($_GET['unauthorize'])) {
	$a->operator_clear();
}

$operator_challenge_failed = false;

if( ($password = $_POST['password'] ?? null) !== null ) {
	$operator_challenge_failed = $a->operator_challenge($password) === false;
}

$a->body_main = function() use ($operator_challenge_failed) { ?>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<fieldset>
			<legend>Password</legend>

			<input type="password" name="password" id="password">

			<input type="submit" value="Submit">
		</fieldset>
	</form>
	<?php if( $operator_challenge_failed === true ) { ?>
		<p>Nope!</p>
	<?php }
};

$a->assemble();
