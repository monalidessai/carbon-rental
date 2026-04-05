<?php
/**
 * Shared database connection bootstrap.
 * This mirrors the existing connection.php config so SignIn pages
 * can include ../database/db_connect.php as expected.
 */

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'carbon';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
     die('Connection failed: ' . $conn->connect_error);
}
?>

