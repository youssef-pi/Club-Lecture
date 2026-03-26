<?php
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /club-lecture/pages/auth/login.php');
    exit;
}

$role = $_SESSION['role'] ?? 'membre';
$nom = $_SESSION['nom'] ?? 'Membre';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Club de Lecture</title>
  <link rel="stylesheet" href="/club-lecture/pages/style/style.css?v=20260326">
</head>
<body>
  <header>
    <nav>
      <a href="/club-lecture/index.php">Accueil</a>
      <a href="/club-lecture/pages/books/list.php">Lectures</a>
      <a href="/club-lecture/pages/auth/logout.php">Deconnexion</a>
    </nav>
  </header>

  <main>
    <h1>Club de Lecture</h1>
    <p>Bienvenue, <?= htmlspecialchars($nom) ?>.</p>
    <?php if ($role !== 'membre'): ?>
      <p>Role: <?= htmlspecialchars($role) ?></p>
    <?php endif; ?>
    <p>Accedez aux lectures du club et suivez vos activites.</p>

    <?php if ($role === 'admin'): ?>
      <section class="admin-panel">
        <h2>Panneau d'administration</h2>
        <div class="admin-actions">
          <a class="admin-action-btn" href="/club-lecture/pages/admin/utilisateurs.php">Gestion des utilisateurs</a>
          <a class="admin-action-btn" href="/club-lecture/pages/admin/avis.php">Gestion des avis</a>
          <a class="admin-action-btn" href="/club-lecture/pages/books/list.php">Gestion des livres</a>
          <a class="admin-action-btn" href="/club-lecture/pages/admin/sessions.php">Gestion des sessions</a>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <footer>
    <p>&copy; 2026 - Projet PHP Club-lecture Y.B & Y.M</p>
  </footer>

  <script src="/club-lecture/pages/style/main.js?v=20260326"></script>
</body>
</html>
