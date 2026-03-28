<?php
require_once __DIR__ . '/../../inclusions/auth.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Methode non autorisee']);
    exit;
}

verifyCsrfOrFail();

$bookId = (int) ($_POST['book_id'] ?? 0);
$pagesLues = (int) ($_POST['pages_lues'] ?? 0);
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($bookId <= 0 || $userId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Donnees invalides']);
    exit;
}

$bookStmt = $mysqli->prepare('SELECT total_pages FROM books WHERE id = ? LIMIT 1');
$bookStmt->bind_param('i', $bookId);
$bookStmt->execute();
$book = $bookStmt->get_result()->fetch_assoc();
$bookStmt->close();

if (!$book) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Livre introuvable']);
    exit;
}

$totalPages = (int) ($book['total_pages'] ?? 0);
if ($totalPages <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Nombre total de pages manquant']);
    exit;
}

if ($pagesLues < 0) {
    $pagesLues = 0;
}
if ($pagesLues > $totalPages) {
    $pagesLues = $totalPages;
}

$pourcentage = (int) round(($pagesLues * 100) / $totalPages);

$checkStmt = $mysqli->prepare('SELECT id FROM progress WHERE book_id = ? AND user_id = ? LIMIT 1');
$checkStmt->bind_param('ii', $bookId, $userId);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($existing) {
    $progressId = (int) $existing['id'];
    $updateStmt = $mysqli->prepare('UPDATE progress SET pages_lues = ?, pourcentage = ? WHERE id = ?');
    $updateStmt->bind_param('iii', $pagesLues, $pourcentage, $progressId);
    $updateStmt->execute();
    $updateStmt->close();
} else {
    $insertStmt = $mysqli->prepare('INSERT INTO progress (book_id, user_id, pages_lues, pourcentage) VALUES (?, ?, ?, ?)');
    $insertStmt->bind_param('iiii', $bookId, $userId, $pagesLues, $pourcentage);
    $insertStmt->execute();
    $insertStmt->close();
}

echo json_encode([
    'ok' => true,
    'pages_lues' => $pagesLues,
    'pourcentage' => $pourcentage,
]);

