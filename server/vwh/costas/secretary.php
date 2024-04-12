<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';

function form_submission_preprocess() : array | false {
	if(isset($_POST) === false) {
		return false;
	}
	
	$parameters = [
		'update' => null,
		'arguments' => new UpdateArguments(),
	];

	if(
		isset($_POST['form_button_update'])
		&& isset($_POST['iwee_select']) && $_POST['iwee_select'] === 'null'
		&& isset($_POST['iwee_filter']) && $_POST['iwee_filter'] !== ''
	) {
		$parameters['update'] = Update::SECRETARY_ADD_INTERVIEWEE;
		$parameters['arguments'] = new UpdateArguments(iwee_email: $_POST['iwee_filter']);
	}
	else if (
		isset($_POST['iwee_button_delete'])
		&& isset($_POST['iwee_select']) && $_POST['iwee_select'] !== 'null'
		&& intval($_POST['iwee_select']) !== 0
	) {
		$parameters['update'] = Update::SECRETARY_DELETE_INTERVIEWEE;
		$parameters['arguments'] = new UpdateArguments(iwee_id: intval($_POST['iwee_select']));
	}

	return $parameters['update'] === null ? false : $parameters;
}

if(($parameters = form_submission_preprocess()) !== false) {
	database()->handle_update($parameters['update'], $parameters['arguments']);

	// TODO temporar solution to avoid form re-submit on refresh after coming from a submit
	// can be done better with https://en.wikipedia.org/wiki/Post/Redirect/Get
	// or maybe make the form sumbission async with ajax and display a message of completion
	// or failure (without clearing the form) to avoid sharing data problem
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit(0);
}

// ---

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new AssemblerOperate('Secretary');

$a->operator_ensure(Operator::Secretary);

$a->body_main = function() {

	// foreach ($_POST as $key => $value) {
	// 	echo "{$key} => {$value}<br>";
	// } ?>

	<form id="form" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">

		<fieldset id="iwee"> <!-- interviewee -->
			<legend>Interviewee</legend>

			<input id="iwee_filter" name="iwee_filter" type="text" placeholder="Filter interviewees...">
			<select id="iwee_select" name="iwee_select">
				<!-- filled in scripts -->
			</select>

			<div id="iwee_buttons" class="horizontal_buttons">
				<button id="iwee_button_active_inactive" name="iwee_button_active_inactive" type="submit">Active / Inactive</button>
				<button id="iwee_button_delete" name="iwee_button_delete" type="submit">Delete</button>
			</div>

			<p id="iwee_notice" align="center"></p>
		</fieldset> <!-- interviewee -->
		
		<?php
		// echo '
		// <fieldset id="iwer"> <!-- interviewer -->
		// 	<legend>Interviewer(s)</legend>

		// 	<input id="iwer_filter" type="text" placeholder="Filter interviewers...">

		// 	<div id="iwer_checkboxes">
		// 		<!-- filled in script -->
		// 	</div>

		// 	<div id="iwer_buttons" class="horizontal_buttons">
		// 		<button id="iwer_button_add" name="iwer_button_add" type="submit">Add</button>
		// 	</div>
		// </fieldset> <!-- interviewer -->
		// ';
		?>

		<button id="form_button_update" name="form_button_update" type="submit">Update</button>

	</form>

<?php };

$a->assemble();

?>

<script src="/script/utilities.js"></script>
<script src="/script/secretary.js"></script>
<script>
	const init_data = '<?php
		require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';
		$db = database();

		echo json_encode($db->retrieve("interviewee"));
	?>';
	
	update(JSON.parse(init_data));
</script>
