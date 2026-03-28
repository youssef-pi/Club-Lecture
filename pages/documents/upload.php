<?php
require_once __DIR__ . '/../../inclusions/auth.php';
requireLogin();
restrictToModerator();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /club-lecture/pages/livres/liste.php');
    exit;
}

verifyCsrfOrFail();

$bookId = (int) ($_POST['book_id'] ?? 0);
if ($bookId <= 0) {
    header('Location: /club-lecture/pages/livres/liste.php?error=book');
    exit;
}

if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    header('Location: /club-lecture/pages/livres/voir.php?id=' . $bookId . '&error=upload');
    exit;
}

$file = $_FILES['document'];
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    header('Location: /club-lecture/pages/livres/voir.php?id=' . $bookId . '&error=size');
    exit;
}

$originalName = $file['name'];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
if (!in_array($ext, $allowedExt, true)) {
    header('Location: /club-lecture/pages/livres/voir.php?id=' . $bookId . '&error=type');
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedMime = [
    'application/pdf',
    'image/jpeg',
    'image/png',
];
if (!in_array($mime, $allowedMime, true)) {
    header('Location: /club-lecture/pages/livres/voir.php?id=' . $bookId . '&error=mime');
    exit;
}

$uploadDir = __DIR__ . '/../../televersements/livres';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$storedName = uniqid('doc_', true) . '.' . $ext;
$destination = $uploadDir . '/' . $storedName;
$relativePath = 'televersements/livres/' . $storedName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    header('Location: /club-lecture/pages/livres/voir.php?id=' . $bookId . '&error=move');
    exit;
}

$filename = trim($originalName);
if ($filename === '') {
    $filename = basename($relativePath);
}

$stmt = $mysqli->prepare('INSERT INTO documents (book_id, filename, filepath, mime, size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)');
$userId = (int) ($_SESSION['user_id'] ?? 0);
$size = (int) $file['size'];
$stmt->bind_param('isssii', $bookId, $filename, $relativePath, $mime, $size, $userId);
$stmt->execute();
$stmt->close();

header('Location: /club-lecture/pages/livres/voir.php?id=' . $bookId . '&success=doc_uploaded');
exit;

