<?php

function silent_prompt(string $prompt) : string {
	// works for unix, needs rework to be used on windows
	
	echo $prompt;
	system('stty -echo');

	$input = fgets(STDIN);
	$input = $input === false ? '' : trim($input);

	system('stty echo');
	echo "\n";

	return $input;
}

require_once __DIR__ . './../database.php';
$db = database_admin();

$action = isset($argv[1]) ? strtolower(trim($argv[1])) : null;

$available = [
	"add" => function() use ($db, $argv) {
		$type = isset($argv[2]) ? strtolower(trim($argv[2])) : null;
		$reminder = isset($argv[3]) ? trim($argv[3]) : null;
	
		if ($type == null || $reminder == null) {
			echo "Use two additional arguments: <type> <personal-reminder>\n";
			exit;
		}

		$password = null;
		
		while($password === null) {
			$password_tmp = silent_prompt("Enter password: ");
			$pconfirm_tmp = silent_prompt("Confirm password: ");

			if($password_tmp === '') {
				echo "Password cannot be empty, please try again.\n";
			}
			else if($password_tmp === $pconfirm_tmp) {
				$password = $password_tmp;
			} 
			else {
				echo "Passwords do not match, please try again.\n";
			}
		}

		if($db->operator_add($type, $password, $reminder) === false) {
			echo "Password already exists or another error occured while adding the password.\n";
			exit;
		}

		echo "Operator added.\n";
	},
	
	"remove" => function() use ($db, $argv) {
		$id_or_all = isset($argv[2]) ? trim($argv[2]) : null;

		if ($id_or_all === null) {
			echo "Use one additional argument: <id>\n";
			exit;
		}

		$id_or_all = $id_or_all === 'all' ? true : intval($id_or_all);

		if($db->operator_remove($id_or_all) === false) {
			echo "Something went wrong, no operator removed.\n";
			exit;
		}

		echo "Operator(s) removed.\n";
	},
	
	"view" => function() use ($db) {
		$entries = $db->operator_entries();
		
		if (empty($entries)) {
			echo "No operator(s) found.\n";
			exit;
		}

		foreach ($entries as $entry) {
			echo implode(" ", [
				'id:"'.$entry['id'].'"',
				'type:"'.$entry['type'].'"',
				'reminder:"'.$entry['reminder'].'"'
			]) . "\n";
		}
	},
];

if (in_array($action, array_keys($available)) === false) {
	echo "Invalid action. Please use one of the following as the first argument: ".implode(', ', array_keys($available))."\n";
	exit;
}

$available[$action]();
