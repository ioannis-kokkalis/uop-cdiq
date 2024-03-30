<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/components/costas/operator_access.php';

operator_clear();

if( ($password = $_POST['password'] ?? null) !== null ) {
	$operator_challenge_failed = operator_challenge($password) === false;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="/style/main.css">
	<title>Operator: UoP CDIQ 2024</title>
</head>
<body>
	
	<?php include $_SERVER['DOCUMENT_ROOT'] . '/.private/components/costas/navigation.php'; ?>

	<hr>

	<header><h1><center>Authorization Required</center></h1></header>

	<hr>

	<main>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<fieldset>
				<legend>Password</legend>
				<label for="password">
					<input type="password" name="password" id="password"><br>
				</label>
				<input type="submit" value="Submit">
			</fieldset>
		</form>
		<?php if( isset($operator_challenge_failed) && $operator_challenge_failed === true ) { ?>
			<p>Nope!</p>
		<?php } ?>
	</main>

	<hr>

	<?php include $_SERVER['DOCUMENT_ROOT'] . '/.private/components/footer.php'; ?>
</body>
</html>
