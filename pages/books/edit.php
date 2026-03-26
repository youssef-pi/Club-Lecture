<?php
require_once __DIR__ . '/../../includes/auth.php';
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
require_once __DIR__ . '/../../includes/header.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrFail();

    $titre = trim($_POST['titre'] ?? '');
    $auteur = trim($_POST['auteur'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date_debut = !empty($_POST['date_debut']) ? $_POST['date_debut'] : null;
    $date_fin = !empty($_POST['date_fin']) ? $_POST['date_fin'] : null;

    if ($titre === '' || $auteur === '') {
        $errors[] = "Le titre et l'auteur sont obligatoires.";
    }

    if (!$errors) {
        $updateStmt = $mysqli->prepare("UPDATE books SET titre = ?, auteur = ?, description = ?, date_debut = ?, date_fin = ? WHERE id = ?");
        $updateStmt->bind_param("sssssi", $titre, $auteur, $description, $date_debut, $date_fin, $id);

        if ($updateStmt->execute()) {
            header('Location: list.php');
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

  <label>Date de debut :</label><br>
  <input type="date" name="date_debut" value="<?= htmlspecialchars($book['date_debut'] ?? '') ?>"><br><br>

  <label>Date de fin :</label><br>
  <input type="date" name="date_fin" value="<?= htmlspecialchars($book['date_fin'] ?? '') ?>"><br><br>

  <button type="submit">Mettre a jour</button>
  <a href="list.php">Annuler</a>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
