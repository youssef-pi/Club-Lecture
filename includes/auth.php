<?php
session_start();
require_once 'database.php';

// Vérifier si l'utilisateur est banni
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $res = mysqli_query($conn, "SELECT statut FROM users WHERE id = $uid");
    $user = mysqli_fetch_assoc($res);
    
    if ($user['statut'] === 'banni') {
        session_destroy();
        header("Location: /club_lecture/pages/auth/login.php?error=banni");
        exit();
    }
}

function isUser();

function isAdmin();

function isModerator();

// Protection de page : redirect si pas admin
function restrictToAdmin() {
    if (!isAdmin()) {
        header("Location: /club_lecture/index.php");
        exit();
    }
}
?>