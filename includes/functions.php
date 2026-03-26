<?php
// Connexion à la base de données
function getDB() {
    $host = 'localhost';
    $dbname = 'club_lecture';
    $user = 'root';
    $pass = ''; // mettre sur $pass ='root' si sur mac

    return new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
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

function ensureCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfInput() {
    $token = ensureCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCsrfOrFail() {
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $postedToken = $_POST['csrf_token'] ?? '';

    if ($sessionToken === '' || $postedToken === '' || !hash_equals($sessionToken, $postedToken)) {
        http_response_code(400);
        die('Requete invalide (CSRF).');
    }
}
?>