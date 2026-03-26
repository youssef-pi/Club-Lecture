<?php
session_start();
require_once __DIR__ . '/../../includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$id = $_GET['id'] ?? 0;
$stmt = $mysqli->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    die("Livre introuvable.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title><?= htmlspecialchars($book['titre']) ?></title>
    <link rel="stylesheet" href="/club-lecture/pages/style/style.css?v=20260326">
</head>
<body>
    <h1><?= htmlspecialchars($book['titre']) ?></h1>
    <p><strong>Auteur :</strong> <?= htmlspecialchars($book['auteur']) ?></p>
    <p><strong>Période :</strong> Du <?= htmlspecialchars($book['date_debut'] ?? 'N/A') ?> au <?= htmlspecialchars($book['date_fin'] ?? 'N/A') ?></p>
    <p><strong>Description :</strong><br> <?= nl2br(htmlspecialchars($book['description'] ?? 'Aucune description.')) ?></p>

    <a href="list.php">Retour à la liste</a>
    <hr>

    <h2>Documents (PDF, Fiches)</h2>
    <p><em>Module documents à venir...</em></p>

    <h2>Avis des membres</h2>
    <p><em>Module avis à venir...</em></p>

    <h2>Ma progression</h2>
    <p><em>Module progression à venir...</em></p>

    <script src="/club-lecture/pages/style/main.js?v=20260326"></script>
</body>
</html>