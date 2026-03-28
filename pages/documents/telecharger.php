<?php
require_once __DIR__ . '/../../inclusions/auth.php';
requireLogin();

$docId = (int) ($_GET['id'] ?? 0);
if ($docId <= 0) {
    http_response_code(404);
    die('Document introuvable.');
}

$stmt = $mysqli->prepare('SELECT id, filename, filepath, mime FROM documents WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $docId);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doc) {
    http_response_code(404);
    die('Document introuvable.');
}

$docPath = (string) ($doc['filepath'] ?? '');
$docPath = str_replace('\\', '/', $docPath);
$docPath = ltrim($docPath, '/');

$candidates = [];

if ($docPath !== '') {
    $candidates[] = $docPath;

    $p = str_replace('uploads/', 'televersements/', $docPath);
    if ($p !== $docPath) {
        $candidates[] = $p;
    }

    $p = str_replace('books/', 'livres/', $docPath);
    if ($p !== $docPath) {
        $candidates[] = $p;
    }

    $p = str_replace('uploads/books/', 'televersements/livres/', $docPath);
    if ($p !== $docPath) {
        $candidates[] = $p;
    }

    $p = str_replace('televersements/books/', 'televersements/livres/', $docPath);
    if ($p !== $docPath) {
        $candidates[] = $p;
    }
}

$baseName = basename($docPath);
if ($baseName !== '') {
    $candidates[] = 'televersements/' . $baseName;
    $candidates[] = 'televersements/livres/' . $baseName;
    $candidates[] = 'uploads/' . $baseName;
    $candidates[] = 'uploads/books/' . $baseName;
}

$fullPath = '';
$checked = [];
foreach ($candidates as $relativePath) {
    if ($relativePath === '' || isset($checked[$relativePath])) {
        continue;
    }
    $checked[$relativePath] = true;

    $candidateFullPath = __DIR__ . '/../../' . $relativePath;
    if (is_file($candidateFullPath)) {
        $fullPath = $candidateFullPath;
        break;
    }
}

if ($fullPath === '') {
    http_response_code(404);
    die('Fichier absent sur le serveur.');
}

$mime = $doc['mime'] ?: 'application/octet-stream';
$ext = strtolower(pathinfo((string) $doc['filename'], PATHINFO_EXTENSION));
if ($ext === 'pdf') {
    $mime = 'application/pdf';
}
$forceDownload = (($_GET['download'] ?? '0') === '1');

$inlineMime = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'image/webp',
];

$disposition = 'attachment';
if (!$forceDownload && in_array($mime, $inlineMime, true)) {
    $disposition = 'inline';
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . basename($doc['filename']) . '"');
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: no-cache, must-revalidate');
readfile($fullPath);
exit;

