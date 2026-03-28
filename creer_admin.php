<?php
// Script utilitaire pour creer/metre a jour un compte admin compatible avec le projet.
// Utilisation CLI: php creer_admin.php "Nom" "email@local" "MotDePasse"

require_once __DIR__ . '/inclusions/database.php';

if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    die("Connexion MySQLi indisponible.\n");
}

function upsertAdmin($mysqli, $nom, $email, $plainPassword) {
    if ($nom === '' || $email === '' || $plainPassword === '') {
        return [false, 'Nom, email et mot de passe obligatoires.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Email invalide.'];
    }

    if (strlen($plainPassword) < 6) {
        return [false, 'Mot de passe trop court (min 6 caracteres).'];
    }

    $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);

    $check = $mysqli->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->bind_param('s', $email);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();

    if ($existing) {
        $userId = (int) $existing['id'];
        $role = 'admin';
        $statut = 'actif';

        $update = $mysqli->prepare('UPDATE users SET nom = ?, password_hash = ?, role = ?, statut = ? WHERE id = ?');
        $update->bind_param('ssssi', $nom, $passwordHash, $role, $statut, $userId);

        if (!$update->execute()) {
            $err = $mysqli->error;
            $update->close();
            return [false, 'Erreur UPDATE: ' . $err];
        }

        $update->close();
        return [true, 'Compte existant mis a jour en admin (actif).'];
    }

    $role = 'admin';
    $statut = 'actif';
    $insert = $mysqli->prepare('INSERT INTO users (nom, email, password_hash, role, statut) VALUES (?, ?, ?, ?, ?)');
    $insert->bind_param('sssss', $nom, $email, $passwordHash, $role, $statut);

    if (!$insert->execute()) {
        $err = $mysqli->error;
        $insert->close();
        return [false, 'Erreur INSERT: ' . $err];
    }

    $insert->close();
    return [true, 'Admin cree avec succes.'];
}

if (PHP_SAPI === 'cli') {
    $nom = trim($argv[1] ?? 'Admin');
    $email = trim($argv[2] ?? 'admin@club.local');
    $plainPassword = $argv[3] ?? '';

    if ($plainPassword === '') {
        echo "Usage: php creer_admin.php \"Nom\" \"email@local\" \"MotDePasse\"\n";
        exit(1);
    }

    $result = upsertAdmin($mysqli, $nom, $email, $plainPassword);
    $ok = $result[0];
    $message = $result[1];
    echo $message . "\n";
    exit($ok ? 0 : 1);
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $plainPassword = $_POST['password'] ?? '';

    $result = upsertAdmin($mysqli, $nom, $email, $plainPassword);
    $success = $result[0];
    $message = $result[1];
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Creer un admin</title>
    <link rel="stylesheet" href="/club-lecture/pages/styles/creer_admin.css?v=20260328-1">
</head>
<body class="create-admin-page">
  <h1>Creer / Mettre a jour un admin</h1>
  <p>Ce script ecrit dans <strong>users.password_hash</strong> et force <strong>role=admin</strong>, <strong>statut=actif</strong>.</p>

  <?php if ($message !== ''): ?>
    <p class="<?= $success ? 'ok' : 'ko' ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="post">
    <label>Nom</label>
    <input type="text" name="nom" required>

    <label>Email</label>
    <input type="email" name="email" required>

    <label>Mot de passe</label>
    <input type="password" name="password" minlength="6" required>

    <button type="submit">Creer / Mettre a jour</button>
  </form>
</body>
</html>

