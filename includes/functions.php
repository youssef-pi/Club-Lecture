<?php
// Retourne la connexion MySQLi partagée
function getDB() {
    global $mysqli;
    return $mysqli;
}

// Vérifier si l'utilisateur est banni
function isBanned($user_id) {
    $db = getDB();
    if (!$db || !($db instanceof mysqli)) {
        return false;
    }

    $uid = (int) $user_id;
    $stmt = $db->prepare("SELECT statut FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    return ($user && ($user['statut'] ?? '') === 'banni');
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