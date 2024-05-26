<?php

define('RESUME_MAX_FILE_SIZE', 5 /* MB */);

ini_set('upload_max_filesize', RESUME_MAX_FILE_SIZE.'M');
ini_set('post_max_size', RESUME_MAX_FILE_SIZE.'M');

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new Assembler('Suggestions');

if(isset($_FILES['resume'])) {
	$body_main = false;

	if(isset($_FILES['resume']['error']) === false) {
		$body_main = function () {
			echo '<p>This is a server error. You should not really encounter this, seek help.</p>';
		};
	}
	else if($_FILES['resume']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['resume']['error'] === UPLOAD_ERR_FORM_SIZE) {
		$body_main = function () {
			echo '<p>We have an upload limit a little less than '.RESUME_MAX_FILE_SIZE.'MB. Your resume is larger than that.</p>';
		};
	}
	else if($_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
		$body_main = function () {
			echo '<p>Seems like an upload error ('.$_FILES['resume']['error'].') occured that we did not consider handling.</p>';
		};
	}
	else /* if($_FILES['resume']['error'] === UPLOAD_ERR_OK) */ {
		function process() : false | array {
			try {
				$resume_file_name = basename($_FILES['resume']['tmp_name']);
				$resume_file_name .= $_FILES['resume']['type'] === 'application/pdf' ? '.pdf' : '.docx';

				$resume_file_curl_ready = curl_file_create(
					$_FILES['resume']['tmp_name'],
					$_FILES['resume']['type'],
					$resume_file_name
				);

				$curl = curl_init();
	
				curl_setopt_array($curl, [
					CURLOPT_PORT => "8000",
					CURLOPT_URL => "http://api/classify_resume",
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 0,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "POST",
					CURLOPT_POSTFIELDS => ["file" => $resume_file_curl_ready],
					CURLOPT_HTTPHEADER => [
						"accept: application/json",
						"Content-Type: multipart/form-data",
					],
				]);
	
				$curl_response = curl_exec($curl);
				$curl_error = curl_error($curl);
	
				curl_close($curl);

				if(empty($curl_error) === false) {
					return false;
				}
	
				$array = json_decode($curl_response, true);

				if(isset($array['tags']) === false || is_array($array['tags']) === false || sizeof($array['tags']) < 1) {
					return false;
				};
	
				return $array['tags'];
			}
			catch (\Throwable $th) {
				return false;
			}
		}

		if(($tags = process()) === false) {
			$body_main = function () {
				echo '<p>The service could not handle you. You either did something inappropriate or we missed something in development.</p>';
			};
		}
		else {
			$body_main = function () use ($tags) {
				print_r($tags); # TODO query the database for jobs that much those tags and their interviewer
				# in case of nothing in the result of the query, just suggest the other page
			};
		}
	}

	if($body_main === false || is_callable($body_main) === false) {
		header('Location: ' . $_SERVER['PHP_SELF'] . '?dafuq');
		exit;
	}

	$a->body_main = $body_main;
}
else {
	$a->body_main = function () {
?>
		<script>
			function processing_popup() {
				let dialog = document.body.appendChild(document.createElement('dialog'));
				dialog.innerHTML = 'This may take some time, do not close the browser please. You can minimize it and use another app, just dont close it completely.';
				dialog.showModal();
			}
		</script>
		
		<p>TODO Rewrite this page better!</p>
		<p>Upload your resume (as .pdf or .docx) and we will suggest what interviewers (companies) are more likely suited for your needs.</p>
		<form method="post" action="<?=$_SERVER['PHP_SELF']?>" enctype="multipart/form-data" onsubmit="processing_popup()">
			<fieldset>
				<legend>Your Resume</legend>
				<label for="resume">
					<input type="file" name="resume" accept=".pdf, .docx" required>
				</label>
				<input type="submit" value="Upload">
			</fieldset>
		</form>
		<p>Your resume will NOT be stored for later use.</p>
		<a href="https://careerday.fet.uop.gr/">I want to explore all job positions!</a>
<?php
	};
}

$a->assemble();
