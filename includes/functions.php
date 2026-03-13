<?php
// Connexion à la base de données
function getDB() {
    $host = 'localhost';
    $dbname = 'club_lecture';
    $user = 'root';
    $pass = ''; // mettre sur $pass ='root' si sur mac
}

// Vérifier si l'utilisateur est banni 
function isBanned($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT statut FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    return ($user && $user['statut'] === 'banni');
}

// Donner la session a l'individu concerné

function isUser() { return isset($_SESSION['user_id']); }

function isAdmin() { return (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'); }

function isModerator() { return (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'moderateur'])); }
?>