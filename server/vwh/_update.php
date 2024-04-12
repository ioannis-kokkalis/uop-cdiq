<?php

// this script is an event source for the JavaScript class EventSource to utilize

// works with PostgreSQL because of LISTEN and NOTIFY thus not using the database.php for the time being
// TODO probably accept PostgreSQL as the database of the choice for the project since otherwise it breaks 

enum Event : string {
	case UPDATE	= 'update';
	case ERROR	= 'uerror';
}

enum Parameter : string {
	case FOR = 'for';
}

// ---

ob_implicit_flush(false);

function produce(Event $event, string $data) : void {
	echo "event:{$event->value}\ndata:{$data}\n\n";
	flush();
}

// ---

if (isset($_GET) === false || isset($_GET[Parameter::FOR->value]) === false) {
	produce(Event::ERROR, "use GET method and parameter '".Parameter::FOR->value."'\n");
	exit(0);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';

$db = database();

$request_data = [ // each must return an array, TODO maybe rework it to classes/interface or enum?
	'secretary' => function () use ($db) : array  {
		// TODO add interviewers too
		return $db->retrieve("interviewee");
	}
];

$for = $_GET[Parameter::FOR->value];

if (in_array($for, array_keys($request_data)) === false) {
	produce(Event::ERROR, "unknown value of '" . Parameter::FOR->value . "' parameter: {$for}\n");
	exit(0);
}

// ---

header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");
header("X-Accel-Buffering: no");

try {
	$conf = (require $_SERVER['DOCUMENT_ROOT'] . '/.private/config.php')['dbms'];

	$listener = new PDO(
		"pgsql:host={$conf['host']};dbname={$conf['dbname']}", $conf['user'], $conf['password'],
		[
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false,
		]
	);
	
	$listener->exec("LISTEN " . Postgres::$UPDATE_CHANNEL . ";");
	
	while (connection_aborted() === 0) {

		$notification = $listener->pgsqlGetNotify(PDO::FETCH_ASSOC, 25_000); // at 30_000 php timesout

		if ($notification === false) {
			continue;
		}

		produce(Event::UPDATE, json_encode($request_data[$for]()));
	}

	$listener = null;
} 
catch (PDOException $e) {
	produce(Event::ERROR, $e->getMessage());
}
