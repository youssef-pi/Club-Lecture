<?php
require_once __DIR__ . '/auth.php';

$loggedIn = isLoggedIn();
$role = currentUserRole();
$appName = defined('APP_NAME') ? constant('APP_NAME') : 'Club de Lecture';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($appName) ?></title>
  <link rel="stylesheet" href="/club-lecture/pages/styles/principal.css?v=20260328-1">
</head>
<body>

<header>
  <nav>
    <a href="/club-lecture/index.php">Accueil</a>

    <?php if ($loggedIn): ?>
      <a href="/club-lecture/pages/livres/liste.php">Livres</a>
      <a href="/club-lecture/pages/sessions/liste.php">Sessions</a>

      <?php if ($role === 'admin' || $role === 'moderateur'): ?>
        <a href="/club-lecture/pages/administration/avis.php">Moderation avis</a>
        <a href="/club-lecture/pages/administration/sessions.php">Gestion sessions</a>
      <?php endif; ?>

      <?php if ($role === 'admin'): ?>
        <a href="/club-lecture/pages/administration/utilisateurs.php">Admin utilisateurs</a>
      <?php endif; ?>

      <a href="/club-lecture/pages/auth/logout.php">Deconnexion</a>
    <?php else: ?>
      <a href="/club-lecture/pages/auth/login.php">Connexion</a>
      <a href="/club-lecture/pages/auth/inscription.php">Inscription</a>
    <?php endif; ?>
  </nav>
</header>

<main>
