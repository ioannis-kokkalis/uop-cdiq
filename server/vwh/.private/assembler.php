<?php

enum Stylesheet : string {
	case Main = "/style/main.css";
}

class Assembler {

	private string		$body_header_title;

	public string		$head_title;
	public Stylesheet 	$head_stylesheet;
	public Closure		$body_main;

	public function __construct(string $body_header_title) {
		$this->head_title = 'UoP CDIQ 2024';
		$this->head_stylesheet = Stylesheet::Main;

		$this->body_header_title = $body_header_title;
		$this->body_main = function() { ?><p>This page has no content yet.</p><?php };
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
		<link rel="stylesheet" href="<?= $this->head_stylesheet->value ?>">
		<link rel="shortcut icon" href="/resources/favicon/normal.svg" type="image/x-icon">
		<?php if((mt_rand() / mt_getrandmax()) >  0.99) { ?>
			<link rel="stylesheet" href="/style/color-shiny.css">
			<link rel="shortcut icon" href="/resources/favicon/shiny.svg" type="image/x-icon">
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

		<main>
			<?= call_user_func($this->body_main); ?>
		</main>

		<div class="spacer"></div>

		<hr>

		<footer>
			<p style="text-align: center;">
				<a href="https://www.uop.gr/">University of the Peloponnese</a> © Career Day 2024 Interviews
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

	private static string $SESSION_OPERATOR_INDEX = "session_operator_index";

	public function __construct(string $body_header_title) {
		parent::__construct($body_header_title);

		$this->head_title = 'Operate: ' . $this->head_title;

		session_start();
	}

	protected function body_header_nav() : void {
		?>
		<a href="/">Home</a>
		<a href="/costas/vasilakis.php">Operate</a>
		<?php
	}

	public function operator_challenge(string $password) : false {
		require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';
		$db = database();

		$type = $db->operator_mapping($password) ?? '';
		$type = Operator::tryFrom($type);

		if( $type === null ) {
			return false;
		}

		$_SESSION[AssemblerOperate::$SESSION_OPERATOR_INDEX] = $type;
		header("Location: /costas/{$type->value}.php");
		exit;
	}

	public static function operator_is(Operator $operator) : bool {
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		return isset($_SESSION[AssemblerOperate::$SESSION_OPERATOR_INDEX]) === true
			&& $_SESSION[AssemblerOperate::$SESSION_OPERATOR_INDEX] === $operator;
	}

	public function operator_ensure(Operator $operator) {
		if( AssemblerOperate::operator_is($operator) === false ) {
			header('Location: /costas/vasilakis.php');
			exit;
		}
	}

	function operator_clear() {
		unset($_SESSION[AssemblerOperate::$SESSION_OPERATOR_INDEX]);
	}

}
