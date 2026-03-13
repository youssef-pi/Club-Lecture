<?php 
// 1. On charge les outils et la sécurité d'abord
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// 2. On affiche le haut de page
include 'includes/header.php'; 
?>


<?php 
// 3. On affiche le bas de page
include 'includes/footer.php'; 
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>index</title>
</head>
<body>
    <main>
    <head>
    <meta charset="UTF-8">
    <title>Mon Super Projet</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="index.php">Accueil</a>
                <?php if($_SESSION['role'] === 'administrateur'): ?>
            <li><a href="gestion_utilisateur.php">Gestion utilisateurs</a></li>
            <li><a href="gestion_avis.php">Gestion avis</a></li>
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
    <script src="js/app.js"></script>
</body>
</html>