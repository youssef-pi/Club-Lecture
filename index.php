<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$role = currentUserRole();
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

define('APP_NAME', 'Club de Lecture');
require_once __DIR__ . '/includes/header.php';
?>

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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
