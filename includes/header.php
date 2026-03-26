<?php
// À mettre tout en haut de chaque page AVANT d'inclure header.php : session_start();
// Ici on suppose que la session est déjà démarrée.
require_once __DIR__ . '/auth.php';

$loggedIn = isLoggedIn();
$role = currentUserRole(); // 'guest' si pas connecté
$appName = defined('APP_NAME') ? constant('APP_NAME') : 'Club de Lecture';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($appName) ?></title>
  <link rel="stylesheet" href="/club-lecture/pages/style/style.css?v=20260326">
</head>
<body>

<header>
  <nav style="display:flex; gap:12px; align-items:center; padding:10px; border-bottom:1px solid #ddd;">
    <strong>Club de Lecture</strong>

    <div style="display:flex; gap:10px; margin-left:20px;">
      <a href="/club-lecture/index.php">Accueil</a>
      <?php if ($loggedIn): ?>
        <a href="/club-lecture/pages/auth/logout.php">Déconnexion</a>
      <?php endif; ?>
    </div>

    <div style="margin-left:auto;">
      <?php if ($loggedIn): ?>
        <span>
          Connecté : <?= htmlspecialchars($_SESSION['nom'] ?? '') ?>
          <?php if (($role ?? 'membre') !== 'membre'): ?>
            (<?= htmlspecialchars($_SESSION['role'] ?? '') ?>)
          <?php endif; ?>
        </span>
      <?php endif; ?>
    </div>
  </nav>
</header>

<main style="padding: 16px;">