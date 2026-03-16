<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
    if ($password === '') $errors[] = "Mot de passe requis.";

    if (!$errors) {
        $stmt = $conn->prepare("SELECT id, nom, email, password_hash, role, statut FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            $errors[] = "Email ou mot de passe incorrect.";
        } else {
            $user = $res->fetch_assoc();

            if ($user['statut'] !== 'actif') {
                $errors[] = "Compte désactivé/banni.";
            } elseif (!password_verify($password, $user['password_hash'])) {
                $errors[] = "Email ou mot de passe incorrect.";
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                header('Location: ../dashboard.php');
                exit;
            }
        }

        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Connexion</title>
</head>
<body>
  <h1>Connexion</h1>

  <?php if ($errors): ?>
    <ul style="color:red;">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post">
    <label>Email</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required><br><br>

    <label>Mot de passe</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Se connecter</button>
  </form>

  <p>Pas de compte ? <a href="register.php">Inscription</a></p>
</body>
</html>