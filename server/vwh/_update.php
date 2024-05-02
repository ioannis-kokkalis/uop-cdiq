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
						return $db->retrieve("interviewee"); # TODO update when retrive gets updates to work better with db views?
					}
				},
				'gatekeeper' =>	function () use ($db) : array {
					require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';
					
					if( AssemblerOperate::operator_is(Operator::Gatekeeper) === false ) {
						return [];
					}
					else {
						return []; # TODO update when retrive gets updates to work better with db views?
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

			function form_submission_preprocess() : array | false {
				if(isset($_POST) === false) {
					return false;
				}
				
				$update_known = intval($_GET[Parameter::WANT_TO_MAKE_CHANGES->value]);

				$parameters = [
					'update' => null,
					'arguments' => new UpdateArguments($update_known),
				];
			
				if(
					isset($_POST['form_button_update'])
					&& isset($_POST['iwee_select']) && $_POST['iwee_select'] === 'null'
					&& isset($_POST['iwee_filter']) && $_POST['iwee_filter'] !== ''
				) {
					$parameters['update'] = Update::SECRETARY_ADD_INTERVIEWEE;
					$parameters['arguments'] = new UpdateArguments($update_known, iwee_email: $_POST['iwee_filter']);
				}
				else if (
					isset($_POST['iwee_button_delete'])
					&& isset($_POST['iwee_select']) && $_POST['iwee_select'] !== 'null'
					&& intval($_POST['iwee_select']) !== 0
				) {
					$parameters['update'] = Update::SECRETARY_DELETE_INTERVIEWEE;
					$parameters['arguments'] = new UpdateArguments($update_known, iwee_id: intval($_POST['iwee_select']));
				}

				return $parameters['update'] === null ? false : $parameters;
			}
			
			if(($parameters = form_submission_preprocess()) !== false) {
				$true_or_reason = $db->update_handle($parameters['update'], $parameters['arguments']);
				echo $true_or_reason === true ? 'ok' : $true_or_reason;
			}
			else {
				echo 'unknown change';
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
