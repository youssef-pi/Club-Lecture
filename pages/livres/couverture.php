<?php
require_once __DIR__ . '/../../inclusions/auth.php';
requireLogin();

$bookId = (int) ($_GET['id'] ?? 0);
if ($bookId <= 0) {
    http_response_code(404);
    exit;
}

$stmt = $mysqli->prepare('SELECT cover_path FROM books WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $bookId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$coverPath = trim((string) ($row['cover_path'] ?? ''));
if ($coverPath === '') {
    http_response_code(404);
    exit;
}

$path = str_replace('\\', '/', $coverPath);
if (strpos($path, '/club-lecture/') === 0) {
    $path = substr($path, strlen('/club-lecture/'));
} elseif (strpos($path, '/') === 0) {
    $path = ltrim($path, '/');
}

$fullPath = __DIR__ . '/../../' . $path;

// Compatibilite simple entre anciens et nouveaux noms de dossier.
if (!is_file($fullPath)) {
    if (strpos($path, 'televersements/couverture/') === 0) {
        $altPath = str_replace('televersements/couverture/', 'uploads/cover/', $path);
        $fullPath = __DIR__ . '/../../' . $altPath;
    } elseif (strpos($path, 'uploads/couverture/') === 0) {
        $altPath = str_replace('uploads/couverture/', 'televersements/couverture/', $path);
        $fullPath = __DIR__ . '/../../' . $altPath;
    } elseif (strpos($path, 'uploads/cover/') === 0) {
        $altPath = str_replace('uploads/cover/', 'televersements/couverture/', $path);
        $fullPath = __DIR__ . '/../../' . $altPath;
    } elseif (strpos($path, 'uploads/covers/') === 0) {
        $altPath = str_replace('uploads/covers/', 'televersements/couverture/', $path);
        $fullPath = __DIR__ . '/../../' . $altPath;
    }
}

if (!is_file($fullPath)) {
    $filename = basename($path);
    $testCouverture = __DIR__ . '/../../televersements/couverture/' . $filename;
    $testCover = __DIR__ . '/../../uploads/cover/' . $filename;
    $testCovers = __DIR__ . '/../../uploads/covers/' . $filename;

    if (is_file($testCouverture)) {
        $fullPath = $testCouverture;
    } elseif (is_file($testCover)) {
        $fullPath = $testCover;
    } elseif (is_file($testCovers)) {
        $fullPath = $testCovers;
    }
}

if (!is_file($fullPath)) {
    http_response_code(404);
    exit;
}

$mime = '';
$finfo = finfo_open(FILEINFO_MIME_TYPE);
if ($finfo !== false) {
    $mime = (string) finfo_file($finfo, $fullPath);
    finfo_close($finfo);
}

$allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowedMime, true)) {
    $mime = 'application/octet-stream';
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: no-cache, must-revalidate');
readfile($fullPath);
exit;

