<?php
require_once __DIR__ . '/../../inclusions/auth.php';
requireLogin();

define('APP_NAME', 'Lectures du Club');
require_once __DIR__ . '/../../inclusions/header.php';

$role = currentUserRole();
$query = "SELECT id, titre, auteur, cover_path, total_pages FROM books ORDER BY id DESC";
$result = mysqli_query($mysqli, $query);
?>

<h1>Lectures du Club</h1>

<p>
  <a href="/club-lecture/index.php">Retour a l'accueil</a>
  <?php if (isModerator()): ?>
    | <a href="ajouter.php"><button type="button">Ajouter un livre</button></a>
  <?php endif; ?>
</p>

<?php if (!$result || mysqli_num_rows($result) === 0): ?>
  <p>Aucun livre enregistre pour le moment.</p>
<?php else: ?>
  <div class="books-table-wrapper">
    <table class="books-table">
      <thead>
        <tr>
          <th>Cover</th>
          <th>Infos</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($book = mysqli_fetch_assoc($result)): ?>
          <tr>
            <td class="books-cover-cell">
              <?php if (!empty($book['cover_path'])): ?>
                <img class="books-cover" src="/club-lecture/pages/livres/couverture.php?id=<?= (int) $book['id'] ?>" alt="Cover de <?= htmlspecialchars($book['titre']) ?>">
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td class="books-info-cell">
              <p><strong>Titre:</strong> <?= htmlspecialchars($book['titre']) ?></p>
              <p><strong>Auteur:</strong> <?= htmlspecialchars($book['auteur']) ?></p>
              <p><strong>Pages:</strong> <?= (int) ($book['total_pages'] ?? 0) ?></p>
            </td>
            <td class="books-actions-cell">
              <a href="voir.php?id=<?= (int) $book['id'] ?>">Voir</a>

              <?php if (isModerator()): ?>
                <a href="modifier.php?id=<?= (int) $book['id'] ?>">Modifier</a>
              <?php endif; ?>

              <?php if (isAdmin()): ?>
                <form method="post" action="delete.php" class="action-inline-form" data-confirm="Supprimer ce livre ?">
                  <?= csrfInput() ?>
                  <input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
                  <button type="submit" class="btn-danger">Supprimer</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../inclusions/footer.php'; ?>

