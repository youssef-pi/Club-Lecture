<?php
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /club-lecture/pages/auth/login.php');
    exit;
}

$role = $_SESSION['role'] ?? 'membre';
$nom = $_SESSION['nom'] ?? 'Membre';

$upcomingSessions = [];
$sessionsRes = $mysqli->query("SELECT s.id, s.titre, s.date_heure, b.titre AS book_titre, b.id AS book_id
                 FROM sessions s
                 INNER JOIN books b ON b.id = s.book_id
                 WHERE s.date_heure >= NOW()
                 ORDER BY s.date_heure ASC
                 LIMIT 5");
if ($sessionsRes) {
  while ($row = $sessionsRes->fetch_assoc()) {
    $upcomingSessions[] = $row;
  }
}
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

    <section class="admin-panel">
      <h2>Prochaines sessions</h2>
      <?php if (!$upcomingSessions): ?>
        <p>Aucune session a venir.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($upcomingSessions as $session): ?>
            <li>
              <strong><?= htmlspecialchars($session['titre']) ?></strong>
              - <?= htmlspecialchars($session['date_heure']) ?>
              (Livre: <a href="/club-lecture/pages/books/view.php?id=<?= (int) $session['book_id'] ?>"><?= htmlspecialchars($session['book_titre']) ?></a>)
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </main>

  <footer>
    <p>&copy; 2026 - Projet PHP Club-lecture Y.B & Y.M</p>
  </footer>

  <script src="/club-lecture/pages/style/main.js?v=20260326"></script>
</body>
</html>
