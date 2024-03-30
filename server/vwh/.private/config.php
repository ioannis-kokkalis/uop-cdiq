<?php

// keys should change rarely if not never, values can be changed freely

return [
	"dbms" => [
		"host" => getenv('DB_HOST'),
		"dbname" => getenv('DB_NAME'),
		"user" => getenv('DB_USERNAME'),
		"password" => getenv('DB_PASSWORD'),
	],
];
