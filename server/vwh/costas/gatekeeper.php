<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new AssemblerOperate('Gatekeeper');

$a->operator_ensure(Operator::Gatekeeper);

$a->assemble();
