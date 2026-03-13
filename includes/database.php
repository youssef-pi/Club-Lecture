<?php 
 
$host = 'localhost';
$identifiant = 'root';
$password = '';
$dbname = 'club_lecture';

$mysqli = mysqli_connect( $host, $identifiant, $password, $dbname);
if ($mysqli) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($mysqli, "utf8mb4");

?>