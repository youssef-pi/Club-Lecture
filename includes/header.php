<?php
// À mettre tout en haut de chaque page AVANT d'inclure header.php : session_start();
// Ici on suppose que la session est déjà démarrée.
require_once __DIR__ . '/auth.php';

$loggedIn = isLoggedIn();
$role = currentUserRole(); // 'guest' si pas connecté
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(APP_NAME ?? 'Club de Lecture') ?></title>
  <link rel="stylesheet" href="/club-lecture/assets/css/style.css">
</head>
<body>

<header>
  <nav style="display:flex; gap:12px; align-items:center; padding:10px; border-bottom:1px solid #ddd;">
    <a href="/club-lecture/pages/dashboard.php"><strong>Club de Lecture</strong></a>

    <div style="display:flex; gap:10px; margin-left:20px;">
      <?php if (!$loggedIn): ?>
        <a href="/club-lecture/pages/auth/login.php">Login</a>
        <a href="/club-lecture/pages/auth/register.php">Register</a>
      <?php else: ?>
        <a href="/club-lecture/pages/dashboard.php">Dashboard</a>
        <a href="/club-lecture/pages/books/list.php">Livres</a>
        <a href="/club-lecture/pages/sessions/list.php">Sessions</a>

        <?php if ($role === 'admin'): ?>
          <a href="/club-lecture/pages/admin/users.php">Admin</a>
        <?php endif; ?>

        <a href="/club-lecture/pages/auth/logout.php">Logout</a>
      <?php endif; ?>
    </div>

    <div style="margin-left:auto;">
      <?php if ($loggedIn): ?>
        <span>
          Connecté : <?= htmlspecialchars($_SESSION['nom'] ?? '') ?>
          (<?= htmlspecialchars($_SESSION['role'] ?? '') ?>)
        </span>
      <?php endif; ?>
    </div>
  </nav>
</header>

<main style="padding: 16px;">