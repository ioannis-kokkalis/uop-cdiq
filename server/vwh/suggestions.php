<?php
	define('MODE_GUEST', 'GUEST');
	define('MODE_LOGIN', 'LOGIN');

	$showInitial = false;
	$showFileUploadForm = false;
	$showSuggestionsResult = false;

	if(isset($_POST['submit'])) {
		$showFileUploadForm = true;

		$mode = isset($_POST['mode']) ? $_POST['mode'] : NULL;
		if($mode == MODE_GUEST) {
			// skip
		}
		else if($mode == MODE_LOGIN) {
			// $return_url = 'http://' . "placeholderhost:port" . $_SERVER['REQUEST_URI'];
			// $sso_url = 'https://sso.uop.gr/login?service=' . urlencode($return_url);
			// header('Location: ' . $sso_url);
			// exit();
			// /* TODO look it up with Costas?
			// Application Not Authorized to Use CAS. The application you attempted to authenticate to is not authorized to use CAS. This usually indicates that the application is not registered with CAS, or its authorization policy defined in its registration record prevents it from leveraging CAS functionality, or it's malformed and unrecognized by CAS. Contact your CAS administrator to learn how you might register and integrate your application with CAS.
			// */
		}
		else {
			header('Location: /suggestions.php');
			exit();
		}
	}
	// else if (returning from SSO) {
	// 	$showFileUploadForm = true;
	// 	// TODO
	// 	$mode = MODE_LOGIN;
	// 	$email = sso.email;
	// }
	else if(isset($_FILES['resume'])) {
		$showSuggestionsResult = true;
		
		$suggestionResult = "This feature is not yet implemented.";

		if($_FILES['resume']['error'] == UPLOAD_ERR_NO_FILE) {
			$suggestionResult = "No file uploaded!";
		}
		else if($_FILES['resume']['error'] == UPLOAD_ERR_OK) {
			$fileData = curl_file_create($_FILES['resume']['tmp_name'], $_FILES['resume']['type'], $_FILES['resume']['name']);

			$curl = curl_init();

			curl_setopt_array($curl, [
				CURLOPT_PORT => 8000,
				CURLOPT_URL => "http://api:8000/classify_resume",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => ["file" => $fileData],
				CURLOPT_HTTPHEADER => [
					"accept: application/json",
					"Content-Type: multipart/form-data",
				],
			]);

			$response = curl_exec($curl);
			curl_close($curl);

			$suggestionResult = $response; // TODO return some structure with the result or error not being able to open the file
		}
	}
	else {
		$showInitial = true;
	}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="/style/main.css">
	<title>UoP CDIQ 2024</title>
</head>
<body>
	<?php include $_SERVER['DOCUMENT_ROOT'] . '/.private/components/navigation.php'; ?>

	<hr>

	<header>
		<h1><center>Suggestions for Interviews</center></h1>
	</header>
	
	<hr>

	<?php
	if($showInitial) {
		?>
		<main>
			<p>You have the ability to upload your resume and we will suggest what companies are more likely suited for your needs, so you can apply more precisely for interviews.</p>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
				<fieldset>
					<legend>Conitnue as</legend>
					<label for="guest">
						<input type="radio" name="mode" id="guest" value="<?php echo MODE_GUEST ?>" required>Guest
					</label>
					<label for="login">
						<input type="radio" name="mode" id="login" value="<?php echo MODE_LOGIN ?>" required>UoP Student via Login
					</label>
					<hr>
					<input type="submit" name="submit" value="Proceed">
				</fieldset>
			</form>
			<p>If you wish to log in, we'll store your uploaded resume so that when you're about to start an interview, your interviewer will receive your resume automatically, else your resume will be used only for the suggestions.</p>
		</main>
		<?php
	}
	else if($showFileUploadForm) {
		?>
		<main>
			<p>Upload your resume (as .pdf or .docx) and we will suggest what companies are more likely suited for your needs.</p>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
				<fieldset>
					<legend>Your Resume</legend>
					<label for="resume">
						<input type="file" name="resume" accept=".pdf, .docx" required>
					</label>
					<hr>
					<input type="submit" name="file_upload" value="Upload">
				</fieldset>
			</form>
			<?php
				if($mode == MODE_GUEST) {
					?><p>Your resume will not be stored for later use.</p><?php
				}
				else if($mode == MODE_LOGIN) {
					?><p>Your resume will be stored for later use as mentioned, under the email address you logged in with.</p><?php
					// TODO add the email received from the SSO
				}
			?>
		</main>
		<?php
	}
	else if($showSuggestionsResult) {
		?>
		<main>
			<p>
			<?php
				echo $suggestionResult;
			?>
			</p>
		</main>
		<?php
	}
	?>

	<hr>
	
	<div style="flex-grow: 1"></div>
	<?php include $_SERVER['DOCUMENT_ROOT'] . '/.private/components/footer.php'; ?>
</body>
</html>
