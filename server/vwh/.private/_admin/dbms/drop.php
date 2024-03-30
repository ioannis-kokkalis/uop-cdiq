<?php

require_once __DIR__ . './../../database.php';
$db = database_admin();

$db->drop();
