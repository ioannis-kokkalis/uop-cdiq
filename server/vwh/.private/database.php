<?php

date_default_timezone_set("UTC");

abstract class UpdateRequest {

	public readonly int $update_id_known; # when creating the request

	public function __construct(int $update_id_known) {
		$this->update_id_known = $update_id_known;
	}

	public final function dispatch(PDO $pdo) : true | string {
		try {
			if($pdo === null) {
				throw new Exception("no connection to the database");
			}

			if($pdo->inTransaction() === false) {
				throw new Exception("connection to the database not in transaction");
			}

			$this->process($pdo);

			return true;
		}
		catch(Throwable $t) {
			return $t->getMessage();
		}
	}

	public function when_dispatch_fails() : void {
		// cleanup stuff that happened anywhere like consturctor but not at 'process'
		// PRACTICALLY used for image resources trashing in case of failure when dispaching
	}

	/**
	 * Assumes the PDO given has connected and selected the database while in a transaction wihout interuptions.
	 * 
	 * The fucntion should not handle exceptions involving the PDO and should throw exceptions when cannot complete the request because of the data given. The message of the exception should be the reason.
	 */
	protected abstract function process(PDO $pdo) : void;

}

class SecretaryAddInterviewee extends UpdateRequest {

	private readonly string $iwee_email;

	public function __construct(int $update_id_known, string $iwee_email) {
		parent::__construct($update_id_known);

		$email = filter_var(trim($iwee_email), FILTER_SANITIZE_EMAIL);
		$email = filter_var($email, FILTER_VALIDATE_EMAIL);

		if($email === false || $email !== $iwee_email) {
			throw new InvalidArgumentException("invalid email address provided");
		}

		$this->iwee_email = $email;
	}

	public function process(PDO $pdo) : void {
		$statement = $pdo->query("INSERT
			INTO interviewee (email, active, available)
			VALUES ('{$this->iwee_email}', true, true)
			ON CONFLICT (email) DO NOTHING;
		");

		if($statement === false) {
			throw new Exception("failed to execute query");
		}
	}
	
}

class SecretaryDeleteInterviewee extends UpdateRequest {

	private readonly int $iwee_id;

	public function __construct(int $update_id_known, int $iwee_id) {
		parent::__construct($update_id_known);

		$this->iwee_id = $iwee_id;
	}

	public function process(PDO $pdo) : void {
		$statement = $pdo->query("DELETE
			FROM interviewee
			WHERE id = {$this->iwee_id}
			AND available = true;
		");
		# TODO probably break it into two queries so the exception can be more spesific

		if($statement === false) {
			throw new Exception("failed to execute query");
		}

		if($statement->rowCount() === 0) {
			throw new Exception("interviewee still unavailable at the moment, can be deleted only when available");
		}		
	}

}

class SecretaryEnqueueDequeue extends UpdateRequest {

	private readonly int $iwee_id;
	private readonly array $iwer_ids_to_enqueue;

	public function __construct(int $update_id_known, int $iwee_id, int ...$iwer_ids_to_enqueue) {
		parent::__construct($update_id_known);

		$this->iwee_id = $iwee_id;
		$this->iwer_ids_to_enqueue = $iwer_ids_to_enqueue;
	}

	protected function process(PDO $pdo): void {
		$timestamp_enqueuing = $pdo->query("SELECT NOW();")->fetch()['now'];

		if(count($this->iwer_ids_to_enqueue) > 0) {
			$insert = "INSERT INTO interview (id_interviewer, id_interviewee, state_, state_timestamp) VALUES ";
	
			$values = [];

			foreach ($this->iwer_ids_to_enqueue as $iwer_id) {
				array_push($values, "({$iwer_id}, {$this->iwee_id}, 'ENQUEUED', '{$timestamp_enqueuing}')");
			}

			$insert .= implode(", ", $values);
			$insert .= " ON CONFLICT ON CONSTRAINT pair_interviewer_interviewee DO ";
			$insert .= "UPDATE
				SET state_timestamp = EXCLUDED.state_timestamp
				WHERE interview.state_ = EXCLUDED.state_
			;";

			if($pdo->query($insert) === false) {
				throw new Exception("failed to execute query");
			}
		}

		$statement = $pdo->query("DELETE
			FROM interview
			WHERE id_interviewee = {$this->iwee_id}
			AND state_ = 'ENQUEUED'
			AND state_timestamp < '{$timestamp_enqueuing}'
		;");

		if($statement === false) {
			throw new Exception("failed to execute query");
		}
	}

};

class SecretaryActiveInactiveFlipInterviewee extends UpdateRequest {

	private readonly int $interviewee_id;

	public function __construct(int $update_id_known, int $interviewee_id) {
		parent::__construct($update_id_known);

		$this->interviewee_id = $interviewee_id;
	}

	protected function process(PDO $pdo): void {
		$statement = $pdo->query("UPDATE interviewee
			SET
				active = NOT active
			WHERE
				id = {$this->interviewee_id}
			RETURNING active;
		");

		if($statement === false) {
			throw new Exception("failed to execute query");
		}

		if($statement->fetch()['active'] === false) {
			$statement = $pdo->query("UPDATE interview
				SET
					state_ = 'ENQUEUED',
					state_timestamp = CURRENT_TIMESTAMP
				WHERE
					id_interviewee = {$this->interviewee_id}
					AND state_ IN ('CALLING', 'DECISION', 'HAPPENING');
			");
			
			if($statement === false) {
				throw new Exception("failed to execute query");
			}
		}
	}

};

class SecretaryAddInterviewer extends UpdateRequest {

	protected static string $iwer_image_resource_url_base = '/resources/images/interviewer/';
	protected static string $iwer_image_resource_url_placeholder = '/resources/images/interviewer/placeholder.svg';

	public static function iwerImageResourceUrlPlaceholder() : string {
		return SecretaryAddInterviewer::$iwer_image_resource_url_placeholder;
	}

	protected readonly string $iwer_name;
	protected readonly string $iwer_table;
	protected readonly string $iwer_image_resource_url;

	public function __construct(
		int $update_id_known,
		string $iwer_name,
		string $iwer_table,
		?array $iwer_image_file,
	) {
		parent::__construct($update_id_known);
		
		$iwer_name = $name = trim($iwer_name);
		# TODO sanitazation and validation of "name"
		if($name !== $iwer_name) {
			throw new InvalidArgumentException("invalid name provided");
		}

		$this->iwer_name = $name;
		
		// ===

		$iwer_table = $table = trim($iwer_table);
		# TODO sanitazation and validation of "table"
		if($table !== $iwer_table) {
			throw new InvalidArgumentException("invalid table provided");
		}

		$this->iwer_table = $table;

		// ===

		if($iwer_image_file !== null && $iwer_image_file['error'] !== UPLOAD_ERR_NO_FILE) {
			if($iwer_image_file['error'] !== UPLOAD_ERR_OK) {
				throw new InvalidArgumentException("invalid image provided, probably too big (error code " . $iwer_image_file['error'] . ")");
			}

			$this->iwer_image_resource_url = $url = SecretaryAddInterviewer::$iwer_image_resource_url_base . uniqid("i");

			# TODO change ownership of images directory to user www-data
			if (move_uploaded_file($iwer_image_file['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . $url) === false) {
				throw new Exception("unable to store the image permanently");
			}
		}
		else {
			$this->iwer_image_resource_url = SecretaryAddInterviewer::$iwer_image_resource_url_placeholder;
		}
	}

	protected function process(PDO $pdo): void {
		$statement = $pdo->query("INSERT
			INTO interviewer (name, image_resource_url, table_number, active, available)
			VALUES ('{$this->iwer_name}', '{$this->iwer_image_resource_url}', '{$this->iwer_table}', true, true);
		");

		if($statement === false) {
			throw new Exception("failed to execute query");
		}
	}

	public function when_dispatch_fails() : void {
		if($this->iwer_image_resource_url === SecretaryAddInterviewer::$iwer_image_resource_url_placeholder) {
			return;
		}

		if(file_exists($_SERVER['DOCUMENT_ROOT'] . $this->iwer_image_resource_url)) {
			unlink($_SERVER['DOCUMENT_ROOT'] . $this->iwer_image_resource_url);
		}
	}

};

class SecretaryEditInterviewer extends SecretaryAddInterviewer {

	private readonly int $iwer_id;

	public function __construct(
		int $update_id_known,
		int $iwer_id,
		string $iwer_name,
		string $iwer_table,
		?array $iwer_image_file,
	) {
		parent::__construct($update_id_known, $iwer_name, $iwer_table, $iwer_image_file);

		$this->iwer_id = $iwer_id;
	}

	protected function process(PDO $pdo): void {
		$statement = $pdo->query("SELECT image_resource_url FROM interviewer WHERE id = {$this->iwer_id};");

		if($statement === false) {
			throw new Exception("failed to execute query");
		}

		$image_url_new = $this->iwer_image_resource_url;
		$image_url_old = $statement->fetch()['image_resource_url'];

		$image_url_update = $image_url_old;

		if($image_url_new !== SecretaryAddInterviewer::$iwer_image_resource_url_placeholder) {
			if($image_url_old !== SecretaryAddInterviewer::$iwer_image_resource_url_placeholder) {
				copy($_SERVER['DOCUMENT_ROOT'] . $image_url_new, $_SERVER['DOCUMENT_ROOT'] . $image_url_old);
				unlink($_SERVER['DOCUMENT_ROOT'] . $image_url_new);
			}
			else {
				$image_url_update = $image_url_new;
			}
		}

		$statement = $pdo->query("UPDATE interviewer
			SET
				name = '{$this->iwer_name}',
				image_resource_url = '{$image_url_update}',
				table_number = '{$this->iwer_table}'
			WHERE
				id = {$this->iwer_id}
			;
		");

		if($statement === false) {
			throw new Exception("failed to execute query");
		}
	}

};

class SecretaryDeleteInterviewer extends UpdateRequest {

	private readonly int $iwer_id;

	public function __construct(int $update_id_known, int $iwer_id) {
		parent::__construct($update_id_known);

		$this->iwer_id = $iwer_id;
	}

	protected function process(PDO $pdo): void {
		$statement = $pdo->query("DELETE
			FROM interviewer
			WHERE id = {$this->iwer_id}
			AND available = true
			RETURNING image_resource_url;
		");
		# TODO probably break it into two queries so the exception can be more specific

		if($statement === false) {
			throw new Exception("failed to execute query");
		}

		if($statement->rowCount() === 0) {
			throw new Exception("interviewer still unavailable at the moment, can be deleted only when available");
		}

		if(($iru = $statement->fetch()['image_resource_url']) !== SecretaryAddInterviewer::iwerImageResourceUrlPlaceholder()) {
			if(file_exists($_SERVER['DOCUMENT_ROOT'] . $iru)) {
				unlink($_SERVER['DOCUMENT_ROOT'] . $iru);
			}
		}
	}

};

class SystemCallingToDecision extends UpdateRequest {

	private readonly int $after_seconds;

	public function __construct(int $update_id_known, int $after_seconds) {
		parent::__construct($update_id_known);

		$this->after_seconds = $after_seconds;
	}

	protected function process(PDO $pdo): void {
		$statement = $pdo->query("UPDATE interview
			SET
				state_ = 'DECISION',
				state_timestamp = CURRENT_TIMESTAMP
			WHERE
				state_ = 'CALLING' AND
				CURRENT_TIMESTAMP - state_timestamp > INTERVAL '{$this->after_seconds} seconds';
		");

		if($statement->rowCount() === 0) {
			throw new Exception("none moved to decision");
		}

		if($statement === false) {
			throw new Exception("failed to execute query");
		}
	}

};

class GatekeeperActiveInactiveFlipInterviewer extends UpdateRequest {

	private readonly int $interviewer_id;

	public function __construct(int $update_id_known, int $interviewer_id) {
		parent::__construct($update_id_known);

		$this->interviewer_id = $interviewer_id;
	}

	protected function process(PDO $pdo): void {
		$statement = $pdo->query("UPDATE interviewer
			SET
				active = NOT active
			WHERE
				id = {$this->interviewer_id}
			RETURNING active;
		");

		if($statement === false) {
			throw new Exception("failed to execute query");
		}

		if($statement->fetch()['active'] === false) {
			$statement = $pdo->query("UPDATE interview
				SET
					state_ = 'ENQUEUED',
					state_timestamp = CURRENT_TIMESTAMP
				WHERE
					id_interviewer = {$this->interviewer_id}
					AND state_ IN ('CALLING', 'DECISION', 'HAPPENING');
			");
			
			if($statement === false) {
				throw new Exception("failed to execute query");
			}
		}
	}

};

class GatekeeperCallingOrDecisionToHappening extends UpdateRequest {

	private readonly int $interview_id;

	public function __construct(int $update_id_known, int $interview_id) {
		parent::__construct($update_id_known);

		$this->interview_id = $interview_id;
	}

	protected function process(PDO $pdo): void {
		$statement = $pdo->query("UPDATE interview
			SET
				state_ = 'HAPPENING',
				state_timestamp = CURRENT_TIMESTAMP
			WHERE
				interview.id = {$this->interview_id}
				AND state_ in ('CALLING', 'DECISION')
				;
		");

		if($statement === false) {
			throw new Exception("failed to execute query");
		}
	}

};

class GatekeeperCallingOrDecisionOrHappeningToDequeued extends UpdateRequest {

	private readonly int $interview_id;

	public function __construct(int $update_id_known, int $interview_id) {
		parent::__construct($update_id_known);

		$this->interview_id = $interview_id;
	}

	protected function process(PDO $pdo): void {
		$statement = $pdo->query("DELETE
			FROM interview
			WHERE id = {$this->interview_id}
			AND state_ in ('CALLING','DECISION','HAPPENING');
		");

		// TODO may confuse in practice, will see, same in other classes
		// if($statement->rowCount() === 0) {
		// 	throw new Exception("has already changed state");
		// }

		if($statement === false) {
			throw new Exception("failed to execute query");
		}
	}

};

class GatekeeperHappeningToCompleted extends UpdateRequest {

	private readonly int $interview_id;

	public function __construct(int $update_id_known, int $interview_id) {
		parent::__construct($update_id_known);

		$this->interview_id = $interview_id;
	}

	protected function process(PDO $pdo): void {
		$statement = $pdo->query("UPDATE interview
			SET
				state_ = 'COMPLETED',
				state_timestamp = CURRENT_TIMESTAMP
			WHERE
				interview.id = {$this->interview_id}
				AND state_ = 'HAPPENING'
				;
		");

		if($statement === false) {
			throw new Exception("failed to execute query");
		}
	}

};

interface Database {

	/**
	 * @return string of the operator type when the password matches
	 * @return false when the password has no match
	 */
	public function operator_mapping(string $password, string &$timestamp) : string|false;

	public function operator_still_alive(string $timestamp) : bool;
	
	/**
	 * @return true on success
	 * @return string on failure with the reason
	 */
	public function update_handle(UpdateRequest $update_request) : true | string;
	
	/**
	 * @return int id
	 */
	public function update_happened_recent() : int;

	public function retrieve(string ...$from_table) : array; # TODO rework to utilize views
	
	public function retrieve_gatekeeper_view() : array;

	public function retrieve_queues_view() : array;
}

interface DatabaseAdmin {
	public function create();
	public function drop();
	public function operator_entries() : array;
	public function operator_add(string $type, string $password, string $reminder) : bool;
	public function operator_remove(int|bool $id) : bool;
}

interface DatabaseJobPositions {
	public function retrieve_jobs_of(int $interviewer_id, bool $untagged_only = false) : array | false;

	public function insert_job(string $title, string $description, int $interviewer_id) : bool;

	public function delete_job(int $id) : bool;

	public function update_jobs_tags(array $jobs_id_tag) : bool;

	public function retrieve_interviewers_and_jobs_with_tags(array $tags) : array | false;
}

class UpdateHandleExpectedException extends Exception {}

class UpdateHandleUnexpectedException extends Exception {}

class Postgres implements Database, DatabaseAdmin, DatabaseJobPositions {

	private array $conf;
	private ?PDO $pdo;
	
	public function __construct(array $configuration) {
		$this->conf = $configuration['dbms'];
	}

	private function connect(bool $with_database_selection, ?callable $on_success = null) {
		$dsn = "pgsql:host={$this->conf['host']}";

		if ($with_database_selection) {
			$dsn .= ";dbname={$this->conf['dbname']}";
		}

		$options = [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false,
		];

		try {
			$this->pdo = new PDO($dsn, $this->conf['user'], $this->conf['password'], $options);

			if( $on_success !== null ) {
				$result = $on_success();
			}

			$this->pdo = null;

			if ( isset($result) ) {
				return $result;
			}

		} catch (PDOException $e) {
			throw new Exception("Connection failed: " . $e->getMessage());
		}
	}

	// ||
	// \/ methods of Database interface
	
	public function operator_mapping(string $password, string &$timestamp) : string|false {
		return $this->connect(true, function() use ($password, &$timestamp) {
			$result = $this->pdo->query("SELECT type, pass, ts FROM operator;");

			if ($result === false || empty($entries = $result->fetchAll())) {
				return false;
			}

			foreach ($entries as $entry) {
				if (password_verify($password, $entry['pass'])) {
					$timestamp = $entry['ts'];
					return $entry['type'];
				}
			}

			return false;
		});
	}

	public function operator_still_alive(string $timestamp) : bool {
		return $this->connect(true, function() use ($timestamp) {
			$result = $this->pdo->query("SELECT 1 FROM operator WHERE ts = '{$timestamp}';");

			return $result !== false && !empty($result->fetchAll());
		});
	}

	public function update_handle(UpdateRequest $update_request) : true | string {
		return $this->connect(true,
			function() use ($update_request) : true | string {
				$result = true;

				try {
					if($this->pdo->beginTransaction() === false) {
						throw new UpdateHandleUnexpectedException("unable to begin transaction");
					}
					
					// TODO (haha) no need to do it for all updates, make it better
					if($this->pdo->query('LOCK TABLE interview IN EXCLUSIVE MODE;') === false) {
						throw new UpdateHandleUnexpectedException("unable to acquire lock for shared data");
					}
					
					$urid = $this->pdo->query("SELECT * FROM update_recent_id;");

					if($urid === false) {
						throw new UpdateHandleUnexpectedException("unable to retrieve recent update");
					}
					
					if($urid->fetch()['recent'] !== $update_request->update_id_known) {
						throw new UpdateHandleExpectedException("some updates happened before your submission, they should have been send to you by now or soon");
					}

					$updated_or_reason = $update_request->dispatch($this->pdo);

					if($updated_or_reason === true) {
						$this->update_handled_routine();
					}
					else {
						throw new UpdateHandleExpectedException($updated_or_reason);
					}

					if($this->pdo->commit() === false) {
						throw new UpdateHandleUnexpectedException("unable to commit transaction");
					}
				}
				catch (UpdateHandleUnexpectedException $e) {
					$result = "(should not happen) " . $e->getMessage();
				}
				catch (UpdateHandleExpectedException $e) {
					$result = $e->getMessage();
				}
				catch (Throwable $th) {
					$result = "(did not know that will happen) " . $th->getMessage();
				}
				finally {
					if($this->pdo->inTransaction()) {
						$this->pdo->rollBack();
					}
				}

				if($result !== true) { // amazing last minute fix
					$update_request->when_dispatch_fails();
				}

				return $result;
			}
		);
	}

	private function update_handled_routine() {

		# TODO (haha) can be more efficient if we do only the needed avaialability fixes after each update request, more complex since the logic is spread out then

		$this->pdo->query("UPDATE interviewee
			SET available = (
				(
					NOT EXISTS (
						SELECT id FROM interview
						WHERE interview.id_interviewee = interviewee.id
						AND state_ NOT IN ('ENQUEUED', 'COMPLETED')
						LIMIT 1
					)
				)
				AND
				interviewee.active
			)
		;");

		$this->pdo->query("UPDATE interviewer
			SET available = (
				(
					NOT EXISTS (
						SELECT id FROM interview
						WHERE interview.id_interviewer = interviewer.id
						AND state_ NOT IN ('ENQUEUED', 'COMPLETED')
						LIMIT 1
					)
				)
				AND
				interviewer.active
			)
		;");

		# ---
		# ENQUEUED interviews to CALLING with respect order via ID

		$query_next_interview_to_calling = "SELECT
			interview.id as id_iw,
			interview.id_interviewee as id_iwee,
			interview.id_interviewer as id_iwer

			FROM interview, interviewee, interviewer
			WHERE	interviewee.id = interview.id_interviewee
			AND		interviewer.id = interview.id_interviewer

			AND interview.state_ = 'ENQUEUED'
			AND interviewee.available = TRUE
			AND interviewer.available = TRUE

			ORDER BY id_iw ASC
			LIMIT 1;
		";

		do {
			$statement = $this->pdo->query($query_next_interview_to_calling);

			if($statement === false) {
				throw new Exception("failed to execute query");
			}
			
			if ($statement->rowCount() === 1) {

				$interview = $statement->fetch();

				if($this->pdo->query("UPDATE interview
					SET state_ = 'CALLING', state_timestamp = CURRENT_TIMESTAMP
					WHERE id = {$interview['id_iw']};
				") === false) {
					throw new Exception("failed to execute query");
				}

				if($this->pdo->query("UPDATE interviewee
					SET available = FALSE
					WHERE id = {$interview['id_iwee']};
				") === false) {
					throw new Exception("failed to execute query");
				}

				if($this->pdo->query("UPDATE interviewer
					SET available = FALSE
					WHERE id = {$interview['id_iwer']};
				") === false) {
					throw new Exception("failed to execute query");
				}

			}

		} while($statement->rowCount() === 1);

		# ---

		if($this->pdo->query("INSERT INTO updates (happened) VALUES (CURRENT_TIMESTAMP);") === false) {
			throw new UpdateHandleUnexpectedException("unable to insert update timestamp");
		}
		# TODO recreate it with triggers in the database when one of the tables is affected?
	}

	public function update_happened_recent() : int {
		return $this->connect(true, function() {
			$statement = $this->pdo->query("SELECT * FROM update_recent_id;");
			return $statement === false ? 0 : $statement->fetch()['recent'];
		});
	}

	public function retrieve(string ...$from_table) : array {
		return $this->connect(true, function () use ($from_table) {
			$retrieved = [];

			$this->pdo->beginTransaction();
			
			try {
				foreach($from_table as $table) {
					$retrieved[$table] = $this->pdo->query("SELECT * FROM {$table};")->fetchAll(); 
				}

				$statement = $this->pdo->query("SELECT * FROM update_recent_id;");
				$retrieved['update'] = $statement === false ? 0 : $statement->fetch()['recent'];
			}
			catch (Throwable $th) {
				$this->pdo->rollBack();
			}

			$this->pdo->commit();

			return $retrieved;
		});
	}

	public function retrieve_gatekeeper_view() : array {
		return $this->connect(true, function () {
			try {
				$this->pdo->beginTransaction();

				$statement = $this->pdo->query("SELECT * FROM view_gatekeeper_iwers;");

				if($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['interviewers'] = $statement->fetchAll();
				
				# ---

				$statement = $this->pdo->query("SELECT * FROM view_gatekeeper_iwees;");

				if($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['interviewees'] = $statement->fetchAll();
				
				# ---

				$statement = $this->pdo->query("SELECT * FROM view_gatekeeper_iws;");

				if($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['interviews'] = $statement->fetchAll(); 

				# ---

				$statement = $this->pdo->query("SELECT * FROM update_recent_id;");
				$retrieved['update'] = $statement === false ? 0 : $statement->fetch()['recent'];
				
				$this->pdo->commit();

				return $retrieved;
			}
			catch (Throwable $th) {
				if($this->pdo->inTransaction()) {
					$this->pdo->rollBack();
				}
			}
			
			return [];
		});
	}

	public function retrieve_queues_view() : array {
		return $this->connect(true, function () {
			try {
				$this->pdo->beginTransaction();

				$statement = $this->pdo->query("SELECT * FROM view_queues_iwers;");

				if($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['interviewers'] = $statement->fetchAll();
				
				# ---

				$statement = $this->pdo->query("SELECT * FROM view_queues_iwees;");

				if($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['interviewees'] = $statement->fetchAll();
				
				# ---

				$statement = $this->pdo->query("SELECT * FROM view_queues_iws;");

				if($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['interviews'] = $statement->fetchAll(); 

				# ---

				$statement = $this->pdo->query("SELECT * FROM view_queues_iws_current;");

				if($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['interviews_current'] = $statement->fetchAll(); 

				# ---

				$statement = $this->pdo->query("SELECT * FROM update_recent_id;");
				$retrieved['update'] = $statement === false ? 0 : $statement->fetch()['recent'];
				
				$this->pdo->commit();

				return $retrieved;
			}
			catch (Throwable $th) {
				if($this->pdo->inTransaction()) {
					$this->pdo->rollBack();
				}
			}
			
			return [];
		});
	}

	// ||
	// \/ methods of DatabaseAdmin interface

	public function create() {
		$result = $this->connect(false, function() {
			$exists = $this->pdo->query("SELECT 1 FROM pg_database WHERE datname = '{$this->conf['dbname']}'")->fetchColumn();
		
			if ($exists !== false) {
				return false;
			}
		
			return $this->pdo->query("CREATE DATABASE {$this->conf['dbname']};") !== false;
		});

		if ($result === false) {
			echo "Database already exists.\n";
			return;
		}

		echo "Database created.\n";

		$result = $this->connect(true, function() {
			$tables = [ // creation queries

				"CREATE TABLE IF NOT EXISTS operator (
					id SERIAL PRIMARY KEY,
					
					pass VARCHAR(255) NOT NULL,
					type VARCHAR(255) NOT NULL,
					reminder VARCHAR(255) NOT NULL,

					ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- should by something unique-random, it does the job for now
				);",

				# ---

				"CREATE TABLE IF NOT EXISTS interviewee (
					id SERIAL PRIMARY KEY,

					email VARCHAR(255) UNIQUE NOT NULL,

					active BOOLEAN NOT NULL,
					available BOOLEAN NOT NULL
				);",

				"CREATE TABLE IF NOT EXISTS interviewer /* or company */ (
					id SERIAL PRIMARY KEY,

					name VARCHAR(255) NOT NULL,
					image_resource_url VARCHAR(255) NOT NULL,
					table_number VARCHAR(255),

					active BOOLEAN NOT NULL,
					available BOOLEAN NOT NULL
				);",

				"CREATE TABLE IF NOT EXISTS job (
					id SERIAL PRIMARY KEY,
					
					title TEXT,
					description TEXT,

					tag VARCHAR(64) DEFAULT NULL,

					id_interviewer INTEGER NOT NULL REFERENCES interviewer(id) ON DELETE CASCADE
				);",

				"CREATE TABLE IF NOT EXISTS interview (
					id SERIAL PRIMARY KEY,

					id_interviewer INTEGER NOT NULL REFERENCES interviewer(id) ON DELETE CASCADE,
					id_interviewee INTEGER NOT NULL REFERENCES interviewee(id) ON DELETE CASCADE,
					CONSTRAINT pair_interviewer_interviewee UNIQUE (id_interviewer, id_interviewee),

					state_ VARCHAR(255) CHECK (state_ IN (
						'ENQUEUED',
						'CALLING',
						'DECISION',
						'HAPPENING',
						'COMPLETED'
					)),
					state_timestamp TIMESTAMP NOT NULL
				);",

				# ---

				"CREATE TABLE IF NOT EXISTS updates (
					id SERIAL,
					happened TIMESTAMP NOT NULL -- in UTC
				);",

				# ---

				"CREATE VIEW update_recent_id AS SELECT COALESCE(MAX(id),0) AS recent FROM updates;",

				"CREATE VIEW view_gatekeeper_iwers AS
					SELECT
						id,
						name,
						image_resource_url,
						table_number,
						active,
						available
					FROM interviewer
					ORDER BY name;
				",

				"CREATE VIEW view_gatekeeper_iwees AS
					SELECT
						id,
						email
					FROM interviewee
					ORDER BY id;
				",

				"CREATE VIEW view_gatekeeper_iws AS
					SELECT DISTINCT ON (i.id_interviewer) i.*
					FROM interview i
					WHERE i.state_ in ('CALLING', 'DECISION', 'HAPPENING')
					ORDER BY i.id_interviewer ASC, i.state_timestamp DESC;
				",

				"CREATE VIEW view_queues_iwers AS
					SELECT
						id,
						name,
						image_resource_url,
						table_number,
						active
					FROM interviewer
					ORDER BY name;
				",

				"CREATE VIEW view_queues_iwees AS
					SELECT id, available FROM interviewee;
				",

				"CREATE VIEW view_queues_iws AS
					SELECT * FROM interview ORDER BY id ASC;
				",

				"CREATE VIEW view_queues_iws_current AS
					SELECT DISTINCT ON (i.id_interviewer) i.*
					FROM interview i
					WHERE i.state_ in ('CALLING', 'DECISION', 'HAPPENING')
					ORDER BY i.id_interviewer ASC, i.state_timestamp DESC;
				",

				// // keep it in case of need can be removed in later comments
				// "CREATE VIEW update_recent_ms AS
				// 	SELECT COALESCE(
				// 		FLOOR( MAX(EXTRACT(EPOCH FROM happened)) * 1000 ),
				// 		0
				// 	) AS recent
				// 	FROM update_timestamps
				// ;",

			];

			foreach($tables as $t) {
				if($this->pdo->query($t) === false)
					return false;
			}

			return true;
		});

		if ($result === false) {
			echo "Tables creation failed. Dropping database.\n";
			$this->drop();
			return;
		}

		echo "Tables created.\n";
	}

	public function drop() {
		$result = $this->connect(false, function() {
			if ($this->pdo->query("DROP DATABASE IF EXISTS {$this->conf['dbname']};") === false) {
				return false;
			}

			return true;
		});

		if ($result === false) {
			echo "Database did not drop.\n";
			return;
		}

		echo "Database dropped.\n";
	}

	public function operator_entries() : array {
		return $this->connect(true, function() {
			$query = "SELECT * FROM operator;";
			$result = $this->pdo->query($query);
			return $result === false ? [] : $result->fetchAll();
		});
	}

	public function operator_add(string $type, string $password, string $reminder) : bool {
		$entries = $this->operator_entries();
		
		return $this->connect(true, function() use ($entries, $type, $password, $reminder) {

			foreach ($entries as $entry) {
				if (password_verify($password, $entry['pass'])) {
					return false;
				}
			}

			$pass = password_hash($password, PASSWORD_BCRYPT);

			return $this->pdo->query("INSERT INTO operator (pass, type, reminder) VALUES ('{$pass}', '{$type}', '{$reminder}');") !== false;	
		});
	}

	public function operator_remove(bool|int $id_or_all) : bool {
		if ($id_or_all === false) {
			return false;
		}

		return $this->connect(true, function() use ($id_or_all) {

			if ($id_or_all === true) {
				$query = "TRUNCATE operator RESTART IDENTITY;";
			}
			else {
				$query = "DELETE FROM operator WHERE id = {$id_or_all};";
			}

			return $this->pdo->query($query) !== false;
		});
	}

	// ||
	// \/ methods of DatabaseJobPositions interface

	public function retrieve_jobs_of(int $interviewer_id, bool $untagged_only = false) : array | false {
		return $this->connect(true, function () use ($interviewer_id, $untagged_only) {
			try {
				$this->pdo->beginTransaction();

				$statement = $this->pdo->query("SELECT id, name, table_number FROM interviewer WHERE id = {$interviewer_id};");
				
				if($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['info'] = $statement->fetch();

				# ---

				$statement = $this->pdo->query("SELECT j.*
					FROM interviewer as i, job as j
					WHERE i.id = j.id_interviewer
					AND i.id = {$interviewer_id}
					".($untagged_only === true ? 'AND j.tag IS NULL' : '')."
					ORDER BY j.id
				;");

				if($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['jobs'] = $statement->fetchAll();

				# ---

				$this->pdo->commit();

				return $retrieved;
			}
			catch (Throwable $th) {
				if($this->pdo->inTransaction()) {
					$this->pdo->rollBack();
				}
			}
			
			return false;
		});
	}

	public function insert_job(string $title, string $description, int $interviewer_id) : bool {
		return $this->connect(true, function () use ($title, $description, $interviewer_id) {
			try {
				$this->pdo->beginTransaction();

				$statement = $this->pdo->prepare("INSERT INTO job (title, description, id_interviewer) VALUES (:title, :description, :interviewer_id)");
				$statement->bindParam(':title', $title, PDO::PARAM_STR);
				$statement->bindParam(':description', $description, PDO::PARAM_STR);
				$statement->bindParam(':interviewer_id', $interviewer_id, PDO::PARAM_INT);
				
				if($statement->execute() === false) {
					throw new Exception('failed execute to query');
				}

				$this->pdo->commit();

				return true;
			}
			catch (Throwable $th) {
				if($this->pdo->inTransaction()) {
					$this->pdo->rollBack();
				}
			}
			
			return false;
		});
	}

	public function delete_job(int $id) : bool {
		return $this->connect(true, function () use ($id) {
			try {
				$this->pdo->beginTransaction();

				$statement = $this->pdo->query("DELETE FROM job WHERE id = {$id};");
				
				if($statement->execute() === false) {
					throw new Exception('failed execute to query');
				}

				$this->pdo->commit();

				return true;
			}
			catch (Throwable $th) {
				if($this->pdo->inTransaction()) {
					$this->pdo->rollBack();
				}
			}
			
			return false;
		});
	}

	public function update_jobs_tags(array $tag_job_ids) : bool {
		return $this->connect(true, function () use ($tag_job_ids) {
			try {
				$this->pdo->beginTransaction();

				foreach ($tag_job_ids as $tag => $job_ids) {
					$job_ids = implode(", ", $job_ids);

					$statement = $this->pdo->query("UPDATE job
						SET tag = '{$tag}' WHERE id IN ({$job_ids})
					;");
					
					if($statement->execute() === false) {
						throw new Exception('failed execute to query');
					}
				}
				
				$this->pdo->commit();

				return true;
			}
			catch (Throwable $th) {
				if($this->pdo->inTransaction()) {
					$this->pdo->rollBack();
				}
			}
			
			return false;
		});
	}

	public function retrieve_interviewers_and_jobs_with_tags(array $tags) : array | false {
		return $this->connect(true, function () use ($tags) {
			try {
				$this->pdo->beginTransaction();

				$tags = "'".implode("', '", $tags)."'";

				$statement = $this->pdo->query("SELECT
						i.id, i.name, j.title
					FROM
						interviewer as i, job as j
					WHERE
						i.id = j.id_interviewer
						AND j.tag IN ({$tags})
					ORDER BY i.name, i.id, j.id
				;");

				if($statement === false) {
					throw new Exception('failed execute to query');
				}

				$arr = $statement->fetchAll();
				
				$this->pdo->commit();

				return $arr;
			}
			catch (Throwable $th) {
				if($this->pdo->inTransaction()) {
					$this->pdo->rollBack();
				}
			}
			
			return false;
		});
	}
}

function database() : Database {
	return new Postgres(require __DIR__ . '/config.php');
}

function database_admin() : DatabaseAdmin {
	return new Postgres(require __DIR__ . '/config.php');
}

function database_jobpositions() : DatabaseJobPositions {
	return new Postgres(require __DIR__ . '/config.php');
}
