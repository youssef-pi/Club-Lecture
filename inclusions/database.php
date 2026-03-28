<?php 
// Paramètres de connexion
$host = 'localhost';
$identifiant = 'root';
$password = ''; // 'root' si tu es sur Mac (MAMP), sinon vide
$dbname = 'club_lecture';

// Tentative de connexion
$mysqli = mysqli_connect($host, $identifiant, $password, $dbname);

// CORRECTION ICI : On vérifie si la connexion a ÉCHOUÉ (!)
if (!$mysqli) {
    die("Erreur de connexion : " . mysqli_connect_error());
}

// Définition de l'encodage pour gérer les accents
mysqli_set_charset($mysqli, "utf8mb4");
?>