<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
restrictToModerator();

define('APP_NAME', 'Ajouter un livre');
require_once __DIR__ . '/../../includes/header.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrFail();

    $titre = trim($_POST['titre'] ?? '');
    $auteur = trim($_POST['auteur'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date_debut = !empty($_POST['date_debut']) ? $_POST['date_debut'] : null;
    $date_fin = !empty($_POST['date_fin']) ? $_POST['date_fin'] : null;
    $created_by = (int) ($_SESSION['user_id'] ?? 0);

    if ($titre === '' || $auteur === '') {
        $errors[] = "Le titre et l'auteur sont obligatoires.";
    }

    if (!$errors) {
        $stmt = $mysqli->prepare("INSERT INTO books (titre, auteur, description, date_debut, date_fin, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $titre, $auteur, $description, $date_debut, $date_fin, $created_by);

        if ($stmt->execute()) {
            header('Location: list.php');
            exit;
        }

        $errors[] = "Erreur lors de l'ajout : " . $mysqli->error;
        $stmt->close();
    }
}
?>

<h1>Ajouter une nouvelle lecture</h1>

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
  <input type="text" name="titre" required><br><br>

  <label>Auteur :</label><br>
  <input type="text" name="auteur" required><br><br>

  <label>Description :</label><br>
  <textarea name="description" rows="4"></textarea><br><br>

  <label>Date de debut :</label><br>
  <input type="date" name="date_debut"><br><br>

  <label>Date de fin :</label><br>
  <input type="date" name="date_fin"><br><br>

  <button type="submit">Enregistrer le livre</button>
  <a href="list.php">Annuler</a>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
