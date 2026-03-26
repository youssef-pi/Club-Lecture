<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

define('APP_NAME', 'Lectures du Club');
require_once __DIR__ . '/../../includes/header.php';

$role = currentUserRole();
$query = "SELECT id, titre, auteur, cover_path, date_debut, date_fin FROM books ORDER BY date_debut DESC, id DESC";
$result = mysqli_query($mysqli, $query);
?>

<h1>Lectures du Club</h1>

<p>
  <a href="/club-lecture/index.php">Retour a l'accueil</a>
  <?php if (isModerator()): ?>
    | <a href="add.php"><button type="button">Ajouter un livre</button></a>
  <?php endif; ?>
</p>

<?php if (!$result || mysqli_num_rows($result) === 0): ?>
  <p>Aucun livre enregistre pour le moment.</p>
<?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Cover</th>
        <th>Titre</th>
        <th>Auteur</th>
        <th>Date de debut</th>
        <th>Date de fin</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($book = mysqli_fetch_assoc($result)): ?>
        <tr>
          <td>
            <?php if (!empty($book['cover_path'])): ?>
              <img src="<?= htmlspecialchars($book['cover_path']) ?>" alt="Cover de <?= htmlspecialchars($book['titre']) ?>" style="width: 64px; height: auto; border-radius: 4px;">
            <?php else: ?>
              -
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($book['titre']) ?></td>
          <td><?= htmlspecialchars($book['auteur']) ?></td>
          <td><?= htmlspecialchars($book['date_debut'] ?? 'N/A') ?></td>
          <td><?= htmlspecialchars($book['date_fin'] ?? 'N/A') ?></td>
          <td>
            <a href="view.php?id=<?= (int) $book['id'] ?>">Voir</a>

            <?php if (isModerator()): ?>
              | <a href="edit.php?id=<?= (int) $book['id'] ?>">Modifier</a>
            <?php endif; ?>

            <?php if (isAdmin()): ?>
              <form method="post" action="delete.php" class="action-inline-form" onsubmit="return confirm('Supprimer ce livre ?');">
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
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
