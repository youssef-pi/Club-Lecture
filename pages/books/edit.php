<?php
session_start();
require_once __DIR__ . '/../../includes/database.php';

$role = $_SESSION['role'] ?? '';
if ($role !== 'admin' && $role !== 'moderateur') {
    header('Location: ../../403.php');
    exit;
}

$id = $_GET['id'] ?? 0;
$errors = [];

// Récupérer les infos existantes
$stmt = $mysqli->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) die("Livre introuvable.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $auteur = trim($_POST['auteur'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date_debut = !empty($_POST['date_debut']) ? $_POST['date_debut'] : null;
    $date_fin = !empty($_POST['date_fin']) ? $_POST['date_fin'] : null;

    if (empty($titre) || empty($auteur)) {
        $errors[] = "Le titre et l'auteur sont obligatoires.";
    }

    if (empty($errors)) {
        $updateStmt = $mysqli->prepare("UPDATE books SET titre = ?, auteur = ?, description = ?, date_debut = ?, date_fin = ? WHERE id = ?");
        $updateStmt->bind_param("sssssi", $titre, $auteur, $description, $date_debut, $date_fin, $id);
        
        if ($updateStmt->execute()) {
            header('Location: list.php');
            exit;
        } else {
            $errors[] = "Erreur de mise à jour : " . $mysqli->error;
        }
        $updateStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><title>Modifier le livre</title></head>
<body>
    <h1>Modifier : <?= htmlspecialchars($book['titre']) ?></h1>
    <?php if ($errors): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>
    
    <form method="post">
        <label>Titre :</label><br>
        <input type="text" name="titre" value="<?= htmlspecialchars($book['titre']) ?>" required><br><br>

        <label>Auteur :</label><br>
        <input type="text" name="auteur" value="<?= htmlspecialchars($book['auteur']) ?>" required><br><br>

        <label>Description :</label><br>
        <textarea name="description" rows="4"><?= htmlspecialchars($book['description'] ?? '') ?></textarea><br><br>

        <label>Date de début :</label><br>
        <input type="date" name="date_debut" value="<?= htmlspecialchars($book['date_debut'] ?? '') ?>"><br><br>

        <label>Date de fin :</label><br>
        <input type="date" name="date_fin" value="<?= htmlspecialchars($book['date_fin'] ?? '') ?>"><br><br>

        <button type="submit">Mettre à jour</button>
        <a href="list.php">Annuler</a>
    </form>
</body>
</html>