<?php

// use pdo-sqlite.
$pdo = new \PDO('sqlite:./db/database.db');

$query = 'CREATE TABLE IF NOT EXISTS User (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    dob VARCHAR(255),
    age INTEGER
);';
$pdo->query($query);

$query = 'CREATE TABLE IF NOT EXISTS Address (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    address VARCHAR(255) NOT NULL
);';
$pdo->query($query);

$query = 'SELECT * FROM User u LEFT JOIN Address a on a.user_id = u.id';
$statement = $pdo->query($query);
$users = $statement->fetchAll();

?>

<?php foreach ($users as $user) : ?>
    name: <?=$user['name'];?><br />
    dob: <?=$user['dob'];?><br />
    age: <?=$user['age'];?> years<br />
    address: <?=$user['address']?><br />
    <hr />
<?php endforeach; ?>
