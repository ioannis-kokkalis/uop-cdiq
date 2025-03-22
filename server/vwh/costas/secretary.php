<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new AssemblerOperateSecretary();

$a->operator_ensure(Operator::Secretary);

// ---

if (isset($_GET['interviewer_id'])) { // interviewer job positions management
	// TODO is funky, doesn't care about updates, can break
	// we will be using it carefully, it is a rush build

	$interviewer_id = null;

	function something_went_wrong(string $reason = 'unknown') {
		global $interviewer_id;
		# echo $reason;
		header("Location: /costas/secretary.php?err".($interviewer_id !== 'null' ? "&interviewer_id={$interviewer_id}" : ''));
		exit(0);
	};

	if(($interviewer_id = $_GET['interviewer_id'] ?? 'null') === 'null') {
		something_went_wrong('no id');
	}

	require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';

	$db = database_jobpositions();

	$clear_resubmission = true;

	if(isset($_POST) && isset($_POST['submit_add_new_position']) === true) {

		$new_position_title = $_POST['new_position_title'] ?? false;
		$new_position_description = $_POST['new_position_description'] ?? false;
		
		if($new_position_title === false || $new_position_description === false) {
			something_went_wrong('missing data');
		}
		
		$interviewer_id = intval($interviewer_id);
		$new_position_title = trim($new_position_title);
		$new_position_description = trim($new_position_description);

		if($new_position_title === ''
			|| $new_position_description === ''
			|| $db->insert_job($new_position_title, $new_position_description, $interviewer_id) === false
		) {
			something_went_wrong('incomplete data or db error');
		}
	}
	else if(isset($_POST) && isset($_POST['submit_delete'])
		&& isset($_POST['job_id']) && $_POST['job_id'] !== 'null'
	) {
		if($db->delete_job(intval($_POST['job_id'])) === false) {
			something_went_wrong('failed to delete job');
		}
		
	}
	else if(isset($_POST) && isset($_POST['evaluate_tags'])) {
		$interviewer = $db->retrieve_jobs_of($interviewer_id, true);

		if($interviewer === false || is_array($interviewer) === false) {
			something_went_wrong('cant retrieve jobs');
		}
		
		$jobs_id_description = $interviewer['jobs'];

		foreach ($jobs_id_description as &$job) {
			unset($job['id_interviewer']);
			unset($job['tag']);
			$job['description'] = implode(" ", [$job['title'], $job['description']]);
			unset($job['title']);
		}

		if(sizeof($jobs_id_description) <= 0) {
			something_went_wrong('no jobs to tag');
		}

		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_PORT => "8000",
			CURLOPT_URL => "http://api/classify_job_descriptions",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => json_encode($jobs_id_description),
			CURLOPT_HTTPHEADER => [
				"Content-Type: application/json"
			],
		]);
		
		$response = curl_exec($curl);
		if ($x = curl_error($curl)) {
			something_went_wrong('curl: '.$x);
		}

		curl_close($curl);

		$response = json_decode($response, true);

		$tag_job_ids = [];

		foreach ($response as $key => $job) {
			if(isset($tag_job_ids[$job['tag']]) === false) {
				$tag_job_ids[$job['tag']] = [];
			}

			array_push($tag_job_ids[$job['tag']], $job['id']);
		}

		if($db->update_jobs_tags($tag_job_ids) === false) {
			something_went_wrong('cant update job tags');
		}
	}
	else {
		$clear_resubmission = false;
	}

	if($clear_resubmission === true) {
		header("Location: /costas/secretary.php?interviewer_id=".$interviewer_id);
		exit(0);
	}

	$interviewer = $db->retrieve_jobs_of($interviewer_id);
	
	if($interviewer === false || is_array($interviewer) === false) {
		something_went_wrong('cant retrieve interviewer details');
	}

	$a->body_main = function () use ($interviewer) { ?>
		<form method="POST" action="/costas/secretary.php?interviewer_id=<?=$interviewer['info']['id']?>"
		class="form-jobpositions" onsubmit="return confirm('Are you sure?');">
			
			<h1 class="info">Job Positions of "<?=$interviewer['info']['name']?>" at Table <?=$interviewer['info']['table_number']?></h1>

			<button type="submit" id="evaluate_tags" name="evaluate_tags">Evaluate Tags (might take some seconds)</button>

			<input type="hidden" id="interviewer_id" name="interviewer_id" value="<?=$interviewer['info']['id']?>">
			<input type="hidden" id="job_id" name="job_id" value="null">
			
			<?php
				foreach ($interviewer['jobs'] as $job) {
					?> <fieldset>
							<legend><?=$job['title']?> (<?=$job['tag'] ?? 'untagged'?>)</legend>
							<textarea name="content" rows="4" style="resize: none;" disabled><?=$job['description']?></textarea>
							<button id="submit_delete" name="submit_delete" onclick="
								document.getElementById('job_id').value = <?=$job['id']?>;
							">Delete</button>
						</fieldset>
					<?php
				}
			?>

			<fieldset>
				<legend>New Position</legend>
				<input type="text" id="new_position_title" name="new_position_title" placeholder="Position title...">
				<textarea id="new_position_description" name="new_position_description" placeholder="Position desription..." rows="4" style="resize: none;"></textarea>
			</fieldset>

			<button type="submit" id="submit_add_new_position" name="submit_add_new_position">Add New Position</button>
		</form>
	<?php };

	$a->assemble();

	exit(0);
}

// ---

$a->body_main = function() { ?>

	<dialog id="dialog-asd9uih" class="info-dialog">
		<p><strong>Add</strong>: interviewees by typing on their respective filter and the "option" to add will show. Same for interviewers but don't.</p>
		<p><strong>Delete</strong>: no you don't.</p>
		<p><strong>Booking Interviews</strong>: select the desired interviewee and the interviewers it requests an interview for, then click Update.</p>
		<p><strong>Booking Interviews</strong>:To Unbook interviews, select an interviewee and deselect interviewers, then click Update. If an interviewer shows as disabled, the interviewee assigned to that interview has moved to Calling or further and you can no longer affect the interview directly.</p>
		<p><strong>Preserve Interviews Order</strong>: when multi-selecting interviewers order of selection is not preserved. So by selecting and Update one interview at a time you guarantee order of interviews.</p>
		<p><strong>Pause</strong>: the interviewee will no longer be available to get to Calling for any interview. If the interviewee was already in an interview, it will go back in queue and we will pretend the Calling never happened in the first place. Order of position in queue will be the same as before. When pausing or unpausing selected interviewers may show incorrectly, deselect and select the Interviewee again or refresh the page.</p>

		<button onclick="document.getElementById('dialog-asd9uih').close();">Thanks!</button>
	</dialog>

	<div id="as8u9dji" class="horizontal_buttons">
		<button id="9a8sdfuh" onclick="document.getElementById('dialog-asd9uih').showModal();
			document.getElementById('dialog-asd9uih').scrollTo(0,0);"
		>Information</button>
		<button onclick="document.getElementById('as8u9dji').style.display = 'none';">Hide</button>
	</div>

	<form id="form"> <!-- submitting with JavaScript XMLHttpRequest -->
		<!--  TODO (haha) maybe consider static form submission in case JS is disabled -->

		<button type="submit"
			onclick="
				event.preventDefault();
				document.getElementById('form_button_update').click();
				return;
			"
		hidden></button>

		<fieldset id="iwee"> <!-- interviewee -->
			<legend>Interviewee</legend>

			<input id="iwee_filter" name="iwee_filter" type="text" placeholder="Filter interviewees...">

			<select id="iwee_select" name="iwee_select">
				<!-- filled in scripts -->
			</select>

			<div id="iwee_buttons" class="horizontal_buttons">
				<button id="iwee_button_active_inactive" name="iwee_button_active_inactive" type="submit">Pause / Unpause</button>
				<button id="iwee_button_delete" name="iwee_button_delete" type="submit">Delete</button>
			</div>

			<p id="iwee_notice" style="text-align: center;"></p>
		</fieldset> <!-- interviewee -->

		<fieldset id="iwer_fieldset"> <!-- interviewer -->
			<legend>Interviewer(s)</legend>

			<input id="iwer_filter" type="text" placeholder="Filter interviewers...">

			<div id="iwer_checkboxes">
				<!-- filled in script -->
			</div>

			<div id="iwer_buttons" class="horizontal_buttons">
				<button id="iwer_button_add" name="iwer_button_add" type="button">Add</button>
			</div>
		</fieldset> <!-- interviewer -->

		<button id="form_button_update" name="form_button_update" type="submit">Update</button>

	</form>

	<dialog id="iwer_info_dialog">
		<form id="iwer_form" method="dialog">
			
			<button type="submit"
				onclick="
					event.preventDefault();
					document.getElementById('iwer_info_dialog_confirm').click();
					return;
				"
			hidden></button>

			<input type="text" id="iwer_info_dialog_id" name="iwer_info_dialog_id" value="null" hidden>
			
			<label for="iwer_info_dialog_name">Name:
				<input type="text" id="iwer_info_dialog_name" name="iwer_info_dialog_name" required>
			</label>

			<label for="iwer_info_dialog_table">Table:
				<input type="text" id="iwer_info_dialog_table" name="iwer_info_dialog_table">
			</label>

			<label for="iwer_info_dialog_image">Image:
				<input type="file" id="iwer_info_dialog_image" name="iwer_info_dialog_image" accept="image/*">
			</label>

			<input type="button" value="Job Positions" onclick="
				window.location.href = '/costas/secretary.php?interviewer_id=' + encodeURIComponent(document.getElementById('iwer_info_dialog_id').value);
				return;
			">

			<div class="horizontal_buttons">
				<input type="submit" id="iwer_info_dialog_delete" name="iwer_info_dialog_delete" value="Delete" formnovalidate>
				<input type="submit" id="iwer_info_dialog_abort" name="iwer_info_dialog_abort" value="Abort" formnovalidate autofocus>
				<input type="submit" id="iwer_info_dialog_confirm" name="iwer_info_dialog_confirm" value="Confirm">
			</div>
		</form>
	</dialog>

<?php };

$a->assemble();

?>

<script src="/script/utilities.js"></script>
<script src="/script/short_polling.js"></script>
<script src="/script/submit.js"></script>
<script src="/script/secretary.js"></script>
<script>
	short_polling(2 /* seconds */, /* for */ 'secretary', /* to retrieve */ (data) => {
		update(data);
	});
</script>
