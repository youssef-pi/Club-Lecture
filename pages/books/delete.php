<?php
session_start();
require_once __DIR__ . '/../../includes/database.php';

// Seul l'Admin peut supprimer un livre [cite: 53]
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../403.php');
    exit;
}

$id = $_GET['id'] ?? 0;

if ($id) {
    $stmt = $mysqli->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

header('Location: list.php');
exit;