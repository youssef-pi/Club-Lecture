<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
restrictToAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /club-lecture/pages/books/list.php');
    exit;
}

verifyCsrfOrFail();

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    $stmt = $mysqli->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

header('Location: /club-lecture/pages/books/list.php');
exit;
