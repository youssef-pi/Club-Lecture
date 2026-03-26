<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
restrictToModerator();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrFail();

    $action = $_POST['action'] ?? '';
    $reviewId = (int) ($_POST['review_id'] ?? 0);

    if ($reviewId <= 0) {
        $errors[] = 'Avis invalide.';
    } else {
        if ($action === 'toggle_mask') {
            $mask = (int) ($_POST['mask'] ?? 0);
            $stmt = $mysqli->prepare('UPDATE reviews SET masque = ? WHERE id = ?');
            $stmt->bind_param('ii', $mask, $reviewId);
            if ($stmt->execute()) {
                $success = 'Etat de moderation mis a jour.';
            } else {
                $errors[] = 'Erreur lors de la moderation.';
            }
            $stmt->close();
        }

        if ($action === 'delete_review') {
            $stmt = $mysqli->prepare('DELETE FROM reviews WHERE id = ?');
            $stmt->bind_param('i', $reviewId);
            if ($stmt->execute()) {
                $success = 'Avis supprime.';
            } else {
                $errors[] = 'Erreur suppression avis.';
            }
            $stmt->close();
        }
    }
}

$reviews = [];
$query = 'SELECT r.id, r.note, r.commentaire, r.masque, r.created_at,
                 u.nom AS user_nom,
                 b.id AS book_id,
                 b.titre AS book_titre
          FROM reviews r
          INNER JOIN users u ON u.id = r.user_id
          INNER JOIN books b ON b.id = r.book_id
          ORDER BY r.created_at DESC';
$res = $mysqli->query($query);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $reviews[] = $row;
    }
}

define('APP_NAME', 'Admin - Gestion des avis');
require_once __DIR__ . '/../../includes/header.php';
?>

    <h1>Moderation des avis</h1>
    <p><a href="/club-lecture/index.php">Retour a l'accueil</a></p>

    <?php if ($success): ?>
      <p class="flash-success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <?php if ($errors): ?>
      <ul class="flash-errors">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if (!$reviews): ?>
      <p>Aucun avis pour le moment.</p>
    <?php else: ?>
      <div class="users-table-wrapper">
        <table class="users-table">
          <thead>
            <tr>
              <th>Livre</th>
              <th>Membre</th>
              <th>Note</th>
              <th>Commentaire</th>
              <th>Etat</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reviews as $review): ?>
              <tr>
                <td>
                  <a href="/club-lecture/pages/books/view.php?id=<?= (int) $review['book_id'] ?>">
                    <?= htmlspecialchars($review['book_titre']) ?>
                  </a>
                </td>
                <td><?= htmlspecialchars($review['user_nom']) ?></td>
                <td><?= (int) $review['note'] ?>/5</td>
                <td><?= nl2br(htmlspecialchars($review['commentaire'] ?? '')) ?></td>
                <td><?= (int) $review['masque'] === 1 ? 'masque' : 'visible' ?></td>
                <td class="users-actions-cell">
                  <form method="post" class="action-inline-form">
                    <?= csrfInput() ?>
                    <input type="hidden" name="action" value="toggle_mask">
                    <input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>">
                    <input type="hidden" name="mask" value="<?= (int) $review['masque'] === 1 ? 0 : 1 ?>">
                    <button type="submit" class="btn-gap"><?= (int) $review['masque'] === 1 ? 'Demasquer' : 'Masquer' ?></button>
                  </form>

                  <form method="post" class="action-inline-form" onsubmit="return confirm('Supprimer cet avis ?');">
                    <?= csrfInput() ?>
                    <input type="hidden" name="action" value="delete_review">
                    <input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>">
                    <button type="submit" class="btn-danger">Supprimer</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
