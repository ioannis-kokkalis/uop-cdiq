<?php

enum Stylesheet : string {
	case Main = "/style/main.php";
}

class Assembler {

	private string		$body_header_title;

	public string		$head_title;
	public Stylesheet 	$head_stylesheet;
	public string		$body_main_id;
	public Closure		$body_main;

	public function __construct(string $body_header_title) {
		$this->head_title = 'UoP CNDIQ 2025';
		$this->head_stylesheet = Stylesheet::Main;

		$this->body_header_title = $body_header_title;
		
		$this->body_main_id = 'some-main';
		$this->body_main = function() { ?><p>This page has no content yet.</p><?php };

		session_start();
	}

	public function assemble() : void {
		header('cache-control: no-cache, no-store, must-revalidate');
		?>
		<!DOCTYPE html>
		<html lang="en">
		<head><?php $this->head(); ?></head>
		<body><?php $this->body(); ?></body>
		</html>
		<?php
	}

	protected function head() : void {
		?>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="<?= $this->head_stylesheet->value ?>?cachebuster=<?= date("YmdH") ?>">
		<link rel="shortcut icon" href="/resources/favicon/normal.svg?cachebuster=<?= date("YmdH") ?>" type="image/x-icon">
		<?php if((mt_rand() / mt_getrandmax()) >  0.99) { ?>
			<link rel="stylesheet" href="/style/color-shiny.css?cachebuster=<?= date("YmdH") ?>">
			<link rel="shortcut icon" href="/resources/favicon/shiny.svg?cachebuster=<?= date("YmdH") ?>" type="image/x-icon">
		<?php } ?>
		<title><?= $this->head_title ?></title>
		<?php
	}

	protected function body() : void {
		?>

		<header>
			<nav><?=  $this->body_header_nav() ?></nav>

			<hr>

			<div class="header_title_container">
				<div class="polygon polygon_accent"></div>
				<div class="polygon polygon_primary"></div>
				<h1 class="text"><?= $this->body_header_title ?></h1>
			</div>
		</header>
		
		<hr>

		<main id="<?=$this->body_main_id?>">
			<?= call_user_func($this->body_main); ?>
		</main>

		<div class="spacer"></div>

		<hr>

		<footer>
			<p style="text-align: center;">
				<a href="https://www.uop.gr/">University of the Peloponnese</a> © Career &#38; Networking Day Interviews Queueing 2025
			</p>
		</footer>

		<?php
	}

	protected function body_header_nav() : void {
		?>
		<a href="/">Home</a>
		<a href="/queues.php">Interviews</a>
		<a href="/suggestions.php">Suggestions</a>
		<?php
	} 

}

enum Operator : string {
	case Secretary = 'secretary';
	case Gatekeeper = 'gatekeeper';
}

class AssemblerOperate extends Assembler {

	private static string $SESSION_OPERATOR_ARRAY = "session_operator_array_#@)_SASD+)K";

	public function __construct(string $body_header_title) {
		parent::__construct($body_header_title);

		$this->head_title = 'Operate: ' . $this->head_title;

		if(isset($_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY]) === false
			|| is_array($_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY]) === false
		) {
			$_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY] = [];
		}
	}

	protected function body_header_nav() : void {
		?>
		<a href="/">Home</a>
		<a href="/costas/vasilakis.php">Authorize</a>
		<a href="/costas/vasilakis.php?unauthorize">Unauthorize</a>
		<?php
			$operators = [];

			if($this->operator_is(Operator::Secretary)) {
				array_push($operators, '<a href="/costas/'.Operator::Secretary->value.'.php">Secretary</a>');
			}
			if($this->operator_is(Operator::Gatekeeper)) {
				array_push($operators, '<a href="/costas/'.Operator::Gatekeeper->value.'.php">Gatekeeper</a>');
			}
			
			if(sizeof($operators) > 0) {
				echo '<div style="width: 100%"></div>' . implode($operators);
			}
		?>
		<?php
	}

	public function operator_challenge(string $password) : false {
		require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';

		$ts = '';

		$type = database()->operator_mapping($password, $ts) ?? '';
		$type = Operator::tryFrom($type);

		if( $type === null ) {
			return false;
		}

		$_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY][$type->value] = $ts;
		header("Location: /costas/{$type->value}.php");
		exit;
	}

	public static function operator_is(Operator $operator) : bool {
		require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';
		
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		return isset($_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY]) === true
			&& isset($_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY][$operator->value]) === true
			&& database()->operator_still_alive($_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY][$operator->value]) === true;
	}

	public function operator_ensure(Operator $operator) {
		if( AssemblerOperate::operator_is($operator) === false ) {
			header('Location: /costas/vasilakis.php');
			exit;
		}
	}

	function operator_clear() {
		unset($_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY]);
		$_SESSION[AssemblerOperate::$SESSION_OPERATOR_ARRAY] = [];
	}

}

class AssemblerOperateSecretary extends AssemblerOperate {

	public function __construct() {
		parent::__construct("Secretary");
	}

	protected function head() : void {
		parent::head();
		?>
		<style>
			html {
				scrollbar-gutter: stable both-edges;
			}
		</style>
		<?php
	}

}
