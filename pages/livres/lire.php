<?php
require_once __DIR__ . '/../../inclusions/auth.php';
requireLogin();

$userId = (int) ($_SESSION['user_id'] ?? 0);
$bookId = (int) ($_GET['id'] ?? 0);
$docId = (int) ($_GET['doc_id'] ?? 0);

if ($bookId <= 0 || $docId <= 0) {
    die('Lecture introuvable.');
}

$bookStmt = $mysqli->prepare('SELECT id, titre, total_pages FROM books WHERE id = ? LIMIT 1');
$bookStmt->bind_param('i', $bookId);
$bookStmt->execute();
$book = $bookStmt->get_result()->fetch_assoc();
$bookStmt->close();

if (!$book) {
    die('Livre introuvable.');
}

$docStmt = $mysqli->prepare('SELECT id, filename, mime FROM documents WHERE id = ? AND book_id = ? LIMIT 1');
$docStmt->bind_param('ii', $docId, $bookId);
$docStmt->execute();
$doc = $docStmt->get_result()->fetch_assoc();
$docStmt->close();

if (!$doc) {
    die('Document introuvable.');
}

if (($doc['mime'] ?? '') !== 'application/pdf') {
    header('Location: /club-lecture/pages/documents/telecharger.php?id=' . $docId);
    exit;
}

$progressStmt = $mysqli->prepare('SELECT pages_lues FROM progress WHERE book_id = ? AND user_id = ? LIMIT 1');
$progressStmt->bind_param('ii', $bookId, $userId);
$progressStmt->execute();
$progressRow = $progressStmt->get_result()->fetch_assoc();
$progressStmt->close();

$initialPage = (int) ($progressRow['pages_lues'] ?? 1);
if ($initialPage <= 0) {
    $initialPage = 1;
}

$bookTotalPages = (int) ($book['total_pages'] ?? 0);
$csrfToken = ensureCsrfToken();

$pdfUrl = '/club-lecture/pages/documents/telecharger.php?id=' . (int) $docId;

define('APP_NAME', 'Lecture: ' . $book['titre']);
require_once __DIR__ . '/../../inclusions/header.php';
?>

<h1><?= htmlspecialchars($book['titre']) ?></h1>
<p><strong>Document:</strong> <?= htmlspecialchars($doc['filename']) ?></p>
<p>
  <a href="/club-lecture/pages/livres/voir.php?id=<?= $bookId ?>">Retour au livre</a>
  | <a href="/club-lecture/pages/documents/telecharger.php?id=<?= $docId ?>&download=1">Telecharger</a>
</p>

<div id="reader-status" class="flash-success reader-status"></div>
<div id="reader-error" class="flash-errors reader-error"></div>

<div class="reader-toolbar">
  <button type="button" id="prev-page">Page precedente</button>
  <span id="page-indicator">Page 1 / 1</span>
  <button type="button" id="next-page">Page suivante</button>
</div>

<div class="reader-canvas-wrap">
  <canvas id="pdf-canvas"></canvas>
</div>

<div id="reader-fallback" class="reader-fallback">
  <p>Le lecteur avance ne peut pas afficher ce PDF. Lecture directe ci-dessous.</p>
  <iframe id="reader-fallback-frame" title="Lecture PDF" loading="lazy" src="<?= htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8') ?>"></iframe>
</div>

<div id="reader-config"
     data-pdf-url="<?= htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8') ?>"
     data-book-id="<?= (int) $bookId ?>"
     data-csrf-token="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
     data-total-book-pages="<?= (int) $bookTotalPages ?>"
     data-initial-page="<?= (int) $initialPage ?>"></div>

<script src="/club-lecture/pages/styles/lire.js?v=20260328-2" type="module"></script>

<?php require_once __DIR__ . '/../../inclusions/footer.php'; ?>

