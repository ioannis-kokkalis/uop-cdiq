<?php

require_once __DIR__ . '/.private/config.php';

$host = $config['dbms_host'];
$dbname = $config['dbms_db_name'];
$user = $config['dbms_username'];
$password = $config['dbms_password'];

$dsn = "pgsql:host=$host;dbname=$dbname";
$pdo = new PDO($dsn, $user, $password);

if ($pdo === false) {
    die("Failed to connect to the database.");
}

echo "Connected successfully to PostgreSQL using PDO.<br>";

$stmt = $pdo->query("SELECT version() AS version");

if ($stmt) {
    // Output data of each row
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "PostgreSQL version: " . $row["version"] . "<br>";
    }
} else {
    echo "No results found.";
}


// Create table test and then delete it
$sql = "CREATE TABLE IF NOT EXISTS test (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    value INT NOT NULL
);";

$stmt = $pdo->query($sql);

if ($stmt) {
    echo "Table 'test' created successfully.<br>";
} else {
    echo "Failed to create table 'test': " . $pdo->errorInfo() . "<br>";
}

$sql = "DROP TABLE test;";
$stmt = $pdo->query($sql);

if ($stmt) {
    echo "Table 'test' dropped successfully.<br>";
} else {
    echo "Failed to drop table 'test': " . $pdo->errorInfo() . "<br>";
}


// Close connection
$pdo = null;
