<?php
session_start();
require_once __DIR__ . '/../../inclusions/database.php';

if (isset($_SESSION['user_id'])) {
  header('Location: /club-lecture/index.php');
  exit;
}

$errors = [];
$nom = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($nom === '' || mb_strlen($nom) < 2) $errors[] = "Nom invalide (min 2 caractères).";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
    if (strlen($password) < 6) $errors[] = "Mot de passe trop court (min 6 caractères).";
    if ($password !== $password2) $errors[] = "Les mots de passe ne correspondent pas.";

    if (!$errors) {
        // Email unique
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $errors[] = "Cet email est déjà utilisé.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'membre';
            $statut = 'actif';

            $stmt2 = $mysqli->prepare("INSERT INTO users (nom, email, password_hash, role, statut) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("sssss", $nom, $email, $hash, $role, $statut);

            if ($stmt2->execute()) {
              session_regenerate_id(true);
              $_SESSION['user_id'] = (int)$mysqli->insert_id;
              $_SESSION['nom'] = $nom;
              $_SESSION['email'] = $email;
              $_SESSION['role'] = $role;

                header('Location: /club-lecture/index.php');
                exit;
            } else {
                $errors[] = "Erreur inscription : " . $mysqli->error;
            }
            $stmt2->close();
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Inscription</title>
  <link rel="stylesheet" href="/club-lecture/pages/styles/principal.css?v=20260328-1">
</head>
<body>
  <h1>Inscription</h1>

  <?php if ($errors): ?>
    <ul class="auth-error-list">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post">
    <label>Nom</label><br>
    <input type="text" name="nom" value="<?= htmlspecialchars($nom) ?>" required><br><br>

    <label>Email</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required><br><br>

    <label>Mot de passe</label><br>
    <input type="password" name="password" required><br><br>

    <label>Confirmer</label><br>
    <input type="password" name="password2" required><br><br>

    <button type="submit">Créer le compte</button>
  </form>

  <p>Déjà un compte ? <a href="login.php">Connexion</a></p>
  <script src="/club-lecture/pages/styles/principal.js?v=20260328-2"></script>
</body>
</html>
