<?php
session_start();
require_once __DIR__ . '/../includes/header.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

if (!isset($_SESSION) || !is_array($_SESSION)) {
    $_SESSION = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projet Club de Lecture</title>
</head>
<body>
    <main>
    <head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="index.php">Accueil</a>
                <?php if (($_SESSION['role'] ?? null) === 'administrateur'): ?>
            <li><a href="gestion_utilisateur.php">Gestion utilisateurs</a></li>
            <li><a href="gestion_avis.php">Gestion avis</a></li>
            <li><a href="/club_lecture/pages/books/list.php">Gérer les lectures (Livres)</a></li>
                 <?php endif; ?>

              <li><a href="logout.php">Déconnexion</a></li>
            </nav>
    </header>
</main>

</body>
</html>

<footer>
        <p>&copy; 2026 - Projet PHP Club-lecture Y.B & Y.M</p>
    </footer>
    <script src="/club_lecture/assets/main.js"></script>
</body>
</html>