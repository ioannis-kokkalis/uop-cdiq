<?php

define('RESUME_MAX_FILE_SIZE', 5 /* MB */);

ini_set('upload_max_filesize', RESUME_MAX_FILE_SIZE.'M');
ini_set('post_max_size', RESUME_MAX_FILE_SIZE.'M');

define('PREV_SUGGESTION_INDEX', 'prev_suggestion_asd8(*U!J9ufpj');

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new Assembler('Suggestions');

$a->body_main_id = 'suggestions-main';

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
			header('Location: ' . $_SERVER['PHP_SELF'] . '?tags=' . urlencode(implode(";", $tags)));
			exit;
		}
	}

	if($body_main === false || is_callable($body_main) === false) {
		header('Location: ' . $_SERVER['PHP_SELF'] . '?dafuq');
		exit;
	}

	$a->body_main = $body_main;
}
else if(isset($_GET['tags'])) {
	$a->body_main = function () {
		$tags = explode(";", urldecode($_GET['tags']));

		require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';

		$db = database_jobpositions();

		$jobs = $db->retrieve_interviewers_and_jobs_with_tags($tags);

		if(sizeof($jobs) === 0) {
?>
			<p>It seems that there are no jobs or internships to recommend.</p>
			<p>Don't worry, this might be an issue on our service. You can always explore <a href="https://careerday.fet.uop.gr/">all related job & intership positions</a>!</p>
<?php
			return;
		}
		
		$grouped_jobs = [];
		foreach ($jobs as $job) {
			$grouped_jobs[$job['name']][] = $job['title'];
		}
		foreach ($grouped_jobs as $name => $titles) {
			echo "<div class='interviewer'><h1>{$name}</h1>";
			foreach ($titles as $title) {
				echo "<p>{$title}</p>";
			}
			echo '</div>';
		}

		echo '<hr>';

		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$_SESSION[PREV_SUGGESTION_INDEX] = $currentUrl;
?>
		<p>We found out that you are suitable for the following:<br><strong><?=implode(", ", $tags)?></strong></p>
		<p>If you think someone else is on the same boat, you can share the current result with them!</p>

		<script src="/script/utilities.js"></script>
		
		<img id="current_url_qr"></img>
		<script>qr_generate(window.location.href, document.getElementById('current_url_qr'));</script>
		
		<button onclick="copy_to_clipboard('<?=$_SESSION[PREV_SUGGESTION_INDEX]?>')">Copy Link</button>
<?php
	};
}
else {
	$a->body_main = function () {
?>
		<script>
			function processing_popup() {
				let dialog = document.body.appendChild(document.createElement('dialog'));
				dialog.innerHTML = '<p>This may take a minute or so. You can use your device, but don\'t close this tab completely.</p>';
				dialog.showModal();
			}
		</script>
		
		<?php
		if(isset($_SESSION[PREV_SUGGESTION_INDEX])) {
			echo '<h2><a href="'.$_SESSION[PREV_SUGGESTION_INDEX].'">Previous Result</a></h2>';
		}
		?>

		<p>Upload your resume (pdf or docx) and we will recommend interviewers with job and intership positions that are suited for you.</p>
		<h5 style="line-height: 1rem; text-align: center;">English resume versions are faster to process and produce more accurate results.</h5>
		<form method="post" action="<?=$_SERVER['PHP_SELF']?>" enctype="multipart/form-data" onsubmit="processing_popup()">
			<fieldset>
				<legend>Your Resume</legend>
				<label for="resume">
					<input type="file" name="resume" accept=".pdf, .docx" required>
				</label>
				<input type="submit" value="Upload">
			</fieldset>
		</form>
		<p>Your resume will NOT be stored.</p>
		<a href="https://careerday.fet.uop.gr/">Explore all job and intership positions!</a>
		<p>This feature is experimental, you should always do your own research as well.</p>
<?php
	};
}

$a->assemble();
