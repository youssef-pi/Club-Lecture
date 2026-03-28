<?php
require_once __DIR__ . '/../../inclusions/auth.php';
requireLogin();
restrictToModerator();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Livre introuvable.');
}

$stmt = $mysqli->prepare("SELECT * FROM books WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    die('Livre introuvable.');
}

define('APP_NAME', 'Modifier un livre');
require_once __DIR__ . '/../../inclusions/header.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrFail();

    $titre = trim($_POST['titre'] ?? '');
    $auteur = trim($_POST['auteur'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $total_pages = (int) ($_POST['total_pages'] ?? 0);

    if ($titre === '' || $auteur === '') {
        $errors[] = "Le titre et l'auteur sont obligatoires.";
    }

    if ($total_pages <= 0) {
        $errors[] = "Le nombre total de pages est obligatoire.";
    }

    if (!$errors) {
        $updateStmt = $mysqli->prepare("UPDATE books SET titre = ?, auteur = ?, description = ?, total_pages = ? WHERE id = ?");
        $updateStmt->bind_param("sssii", $titre, $auteur, $description, $total_pages, $id);

        if ($updateStmt->execute()) {
            header('Location: liste.php');
            exit;
        }

        $errors[] = "Erreur de mise a jour : " . $mysqli->error;
        $updateStmt->close();
    }
}
?>

<h1>Modifier : <?= htmlspecialchars($book['titre']) ?></h1>

<?php if ($errors): ?>
  <ul class="flash-errors">
    <?php foreach ($errors as $e): ?>
      <li><?= htmlspecialchars($e) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form method="post">
  <?= csrfInput() ?>

  <label>Titre :</label><br>
  <input type="text" name="titre" value="<?= htmlspecialchars($book['titre']) ?>" required><br><br>

  <label>Auteur :</label><br>
  <input type="text" name="auteur" value="<?= htmlspecialchars($book['auteur']) ?>" required><br><br>

  <label>Description :</label><br>
  <textarea name="description" rows="4"><?= htmlspecialchars($book['description'] ?? '') ?></textarea><br><br>

  <label>Nombre total de pages :</label><br>
  <input type="number" name="total_pages" min="1" step="1" value="<?= (int) ($book['total_pages'] ?? 0) ?>" required><br><br>

  <button type="submit">Mettre a jour</button>
  <a href="liste.php">Annuler</a>
</form>

<?php require_once __DIR__ . '/../../inclusions/footer.php'; ?>

