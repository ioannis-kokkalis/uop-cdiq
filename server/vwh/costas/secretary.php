<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new AssemblerOperate('Secretary');

$a->operator_ensure(Operator::Secretary);

$a->assemble();
