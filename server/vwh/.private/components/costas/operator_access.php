<?php

define("SESSION_OPERATOR_INDEX", "session_operator_index");

enum Operator : string {
	case Secretary = 'secretary';
	case Gatekeeper = 'gatekeeper';
}

session_start();

function operator_challenge(string $password) : false {
	require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/database.php';
	$db = database();

	$type = $db->operator_mapping($password) ?? '';
	$type = Operator::tryFrom($type);

	if( $type === null ) {
		return false;
	}

	$_SESSION[SESSION_OPERATOR_INDEX] = $type;
	header("Location: /costas/{$type->value}.php");
	exit;
}

function operator_ensure(Operator $operator) {
	if( isset($_SESSION[SESSION_OPERATOR_INDEX]) === false
		|| $_SESSION[SESSION_OPERATOR_INDEX] !== $operator
	) {
		header('Location: /costas/vasilakis.php');
		exit;
	}
}

function operator_clear() {
	unset($_SESSION[SESSION_OPERATOR_INDEX]);
}
