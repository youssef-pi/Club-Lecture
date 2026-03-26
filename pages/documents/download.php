<?php
require_once __DIR__ . '/../../includes/auth.php';
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

$fullPath = __DIR__ . '/../../' . $doc['filepath'];
if (!is_file($fullPath)) {
    http_response_code(404);
    die('Fichier absent sur le serveur.');
}

$mime = $doc['mime'] ?: 'application/octet-stream';
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($doc['filename']) . '"');
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: no-cache, must-revalidate');
readfile($fullPath);
exit;
