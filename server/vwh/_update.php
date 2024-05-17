<?php

define("TIMEZONE", "UTC");
date_default_timezone_set(TIMEZONE);

header("Content-Type: text/plain", true);

if (isset($_GET) === false) {
	echo 'use at least GET method';
	exit(0);
}

# ---

enum Parameter : string {
	case AM_I_UP_TO_DATE = 'am_i_up_to_date';
	case GET_ME_UP_TO_DATE = 'get_me_up_to_date';
	case WANT_TO_MAKE_CHANGES = 'want_to_make_changes';
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';

$db = database();

$parameters = [
	Parameter::AM_I_UP_TO_DATE->value => [
		'handle' => function() use ($db) {
			require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

			if( AssemblerOperate::operator_is(Operator::Gatekeeper) === true ) {
				$db->update_handle(new SystemCallingToDecision(
					$db->update_happened_recent(),
					10 /* seconds */
				));
				// TODO!!! change it to "3 * 60" seconds keep 10 for testing
			}

			$update_known = intval($_GET[Parameter::AM_I_UP_TO_DATE->value]);
			$update_recent = $db->update_happened_recent();
			
			echo $update_recent > $update_known ? 0 : 1;
		},
		'description' => 'expects update id, returns yes (1) when up to date else no (0)'
	],
	Parameter::GET_ME_UP_TO_DATE->value => [
		'handle' => function() use ($db) {
			$for = $_GET[Parameter::GET_ME_UP_TO_DATE->value];

			$clients = [
				'queues' =>		function () use ($db) : array {
					return []; # TODO retrieve appropiete data from the database
				},
				'secretary' =>	function () use ($db) : array {
					require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';
					// TODO? it works, but I don't like AssemblerOperate here, can be decomposed, I don't want to
					
					if( AssemblerOperate::operator_is(Operator::Secretary) === false ) {
						return [];
					}
					else {
						return $db->retrieve("interviewee", "interviewer", "interview");
					}
				},
				'gatekeeper' =>	function () use ($db) : array {
					require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';
					
					if( AssemblerOperate::operator_is(Operator::Gatekeeper) === false ) {
						return [];
					}
					else {
						return $db->retrieve_gatekeeper_view();
					}
				},
			];

			echo json_encode(isset($clients[$for]) === true ? $clients[$for]() : []);
		},
		'description' => "expects 'queues', 'secretary' or 'gatekeeper', returns JSON with appropriate data"
	],
	Parameter::WANT_TO_MAKE_CHANGES->value => [
		'handle' => function() use ($db) {

			require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

			if( AssemblerOperate::operator_is(Operator::Secretary) === false
				&& AssemblerOperate::operator_is(Operator::Gatekeeper) === false ) {
				echo "unauthorized access";
				return;
			}

			$form_submission_preprocess = (function() : UpdateRequest | string {
				if(isset($_POST) === false) {
					return "no POST data found";
				}
				
				$update_known = intval($_GET[Parameter::WANT_TO_MAKE_CHANGES->value]);

				$update_request = null;
				
				try {
					if(
						isset($_POST['form_button_update'])
						&& isset($_POST['iwee_select']) && $_POST['iwee_select'] === 'null'
						&& isset($_POST['iwee_filter']) && $_POST['iwee_filter'] !== ''
					) {
						$update_request = new SecretaryAddInterviewee(
							$update_known,
							$_POST['iwee_filter']
						);
					}
					else if (
						isset($_POST['iwee_button_delete'])
						&& isset($_POST['iwee_select']) && $_POST['iwee_select'] !== 'null'
						&& intval($_POST['iwee_select']) !== 0
					) {
						$update_request = new SecretaryDeleteInterviewee(
							$update_known,
							intval($_POST['iwee_select'])
						);
					}
					else if(
						isset($_POST['iwer_info_dialog_confirm'])
						&& isset($_POST['iwer_info_dialog_id']) && $_POST['iwer_info_dialog_id'] === 'null'
					) {
						$update_request = new SecretaryAddInterviewer(
							$update_known,
							$_POST['iwer_info_dialog_name'],
							$_POST['iwer_info_dialog_table'],
							'/resources/images/placeholder_interviewer.svg', # TODO include from $_FILES
							$_POST['iwer_info_dialog_jobs'],
						);
					}
					else if(
						isset($_POST['iwer_info_dialog_confirm'])
						&& isset($_POST['iwer_info_dialog_id']) && $_POST['iwer_info_dialog_id'] !== 'null'
					) {
						$update_request = new SecretaryEditInterviewer(
							$update_known,
							intval($_POST['iwer_info_dialog_id']),
							$_POST['iwer_info_dialog_name'],
							$_POST['iwer_info_dialog_table'],
							'/resources/images/placeholder_interviewer.svg', # TODO include from $_FILES
							$_POST['iwer_info_dialog_jobs'],
						);
					}
					else if(
						isset($_POST['iwer_info_dialog_delete'])
						&& isset($_POST['iwer_info_dialog_id']) && $_POST['iwer_info_dialog_id'] !== 'null'
					) {
						$update_request = new SecretaryDeleteInterviewer(
							$update_known,
							intval($_POST['iwer_info_dialog_id'])
						);
					}
					else if(
						isset($_POST['form_button_update'])
						&& isset($_POST['iwee_select']) && $_POST['iwee_select'] !== 'null'
					) {
						$update_request = new SecretaryEnqueueDequeue(
							$update_known,
							intval($_POST['iwee_select']),
							...(isset($_POST['interviewers']) ? $_POST['interviewers'] : [])
						);
					}

					# ---

					else if(
						isset($_POST['button_to_happening'])
						&& isset($_POST['input_interview_id'])
					) {
						$update_request = new GatekeeperCallingOrDecisionToHappening(
							$update_known,
							intval($_POST['input_interview_id'])
						);
					}
					else if(
						isset($_POST['button_to_completed'])
						&& isset($_POST['input_interview_id'])
					) {
						$update_request = new GatekeeperHappeningToCompleted(
							$update_known,
							intval($_POST['input_interview_id'])
						);
					}

					return $update_request === null ? 'unknown request' : $update_request;
				}
				catch(Throwable $th) {
					return $th->getMessage();
				}
			})();

			if(is_a($form_submission_preprocess, UpdateRequest::class) === true) {
				$true_or_reason = $db->update_handle($form_submission_preprocess);
				echo $true_or_reason === true ? 'ok' : $true_or_reason;
			}
			else /* if(is_string($fsp) === true) */ {
				echo $form_submission_preprocess;
			}

			return;
		},
		'description' => "expects update id, returns 'ok' when changes are accepted else the reason of denial as string"
	],
];

if(isset($parameters[array_key_first($_GET)])) {
	$parameters[array_key_first($_GET)]['handle']();
}
else {
	echo "No valid parameter given as first, acceptable parameters:\n";
	echo "\n";
	
	foreach ($parameters as $parameter_name => $parameter) {
		echo implode("\n", [
			'-> ' . $parameter_name,
			$parameter['description'],
			"\n"
		]);
	}
}
