<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Vérifier si l'utilisateur est banni
if (isset($_SESSION['user_id'])) {
    $uid = (int) $_SESSION['user_id'];

    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $res = mysqli_query($mysqli, "SELECT statut FROM users WHERE id = {$uid}");

        if ($res) {
            $user = mysqli_fetch_assoc($res);

            if (($user['statut'] ?? null) === 'banni') {
                session_destroy();
                header("Location: /club-lecture/pages/auth/login.php?error=banni");
                exit();
            }
        }
    }
}

function isLoggedIn() {
    return isUser();
}

function currentUserRole() {
    return $_SESSION['role'] ?? 'guest';
}

// Protection de page : redirect si pas admin
function restrictToAdmin() {
    if (!isAdmin()) {
        header("Location: /club-lecture/index.php");
        exit();
    }
}
?>