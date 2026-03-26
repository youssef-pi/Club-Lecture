<?php
session_start();

// Vider session
$_SESSION = [];

// Supprimer cookie session si possible
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header('Location: /club-lecture/pages/auth/login.php');
exit;