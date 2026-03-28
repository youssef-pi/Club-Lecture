<?php
require_once __DIR__ . '/../../inclusions/auth.php';
requireLogin();
restrictToModerator();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /club-lecture/pages/livres/liste.php');
    exit;
}

verifyCsrfOrFail();

$docId = (int) ($_POST['id'] ?? 0);
$bookId = (int) ($_POST['book_id'] ?? 0);
if ($docId <= 0 || $bookId <= 0) {
    header('Location: /club-lecture/pages/livres/liste.php?error=doc_delete');
    exit;
}

$stmt = $mysqli->prepare('SELECT filepath FROM documents WHERE id = ? AND book_id = ? LIMIT 1');
$stmt->bind_param('ii', $docId, $bookId);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($doc) {
    $deleteStmt = $mysqli->prepare('DELETE FROM documents WHERE id = ? AND book_id = ?');
    $deleteStmt->bind_param('ii', $docId, $bookId);
    $deleteStmt->execute();
    $deleteStmt->close();

    $fullPath = __DIR__ . '/../../' . $doc['filepath'];
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

header('Location: /club-lecture/pages/livres/voir.php?id=' . $bookId . '&success=doc_deleted');
exit;

