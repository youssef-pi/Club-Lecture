<?php
require_once __DIR__ . '/../../inclusions/auth.php';
requireLogin();

$userId = (int) ($_SESSION['user_id'] ?? 0);
$role = $_SESSION['role'] ?? 'membre';
$bookId = (int) ($_GET['id'] ?? 0);

if ($bookId <= 0) {
    die('Livre introuvable.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrFail();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_review') {
        $note = (int) ($_POST['note'] ?? 0);
        $commentaire = trim($_POST['commentaire'] ?? '');

        if ($note >= 1 && $note <= 5) {
            $check = $mysqli->prepare('SELECT id FROM reviews WHERE book_id = ? AND user_id = ? LIMIT 1');
            $check->bind_param('ii', $bookId, $userId);
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();
            $check->close();

            if ($existing) {
                $stmt = $mysqli->prepare('UPDATE reviews SET note = ?, commentaire = ?, masque = 0 WHERE id = ?');
                $reviewId = (int) $existing['id'];
                $stmt->bind_param('isi', $note, $commentaire, $reviewId);
            } else {
                $stmt = $mysqli->prepare('INSERT INTO reviews (book_id, user_id, note, commentaire, masque) VALUES (?, ?, ?, ?, 0)');
                $stmt->bind_param('iiis', $bookId, $userId, $note, $commentaire);
            }
            $stmt->execute();
            $stmt->close();
        }

        header('Location: /club-lecture/pages/livres/voir.php?id=' . $bookId . '&success=review_saved');
        exit;
    }

    if ($action === 'save_progress') {
        $pagesLues = (int) ($_POST['pages_lues'] ?? 0);
        if ($pagesLues < 0) {
            $pagesLues = 0;
        }

        $bookPagesStmt = $mysqli->prepare('SELECT total_pages FROM books WHERE id = ? LIMIT 1');
        $bookPagesStmt->bind_param('i', $bookId);
        $bookPagesStmt->execute();
        $bookPagesRow = $bookPagesStmt->get_result()->fetch_assoc();
        $bookPagesStmt->close();

        $totalPages = (int) ($bookPagesRow['total_pages'] ?? 0);
        if ($totalPages <= 0) {
            header('Location: /club-lecture/pages/livres/voir.php?id=' . $bookId . '&success=progress_saved');
            exit;
        }

        if ($pagesLues > $totalPages) {
            $pagesLues = $totalPages;
        }

        $pourcentage = (int) round(($pagesLues * 100) / $totalPages);

        $check = $mysqli->prepare('SELECT id FROM progress WHERE book_id = ? AND user_id = ? LIMIT 1');
        $check->bind_param('ii', $bookId, $userId);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existing) {
            $stmt = $mysqli->prepare('UPDATE progress SET pages_lues = ?, pourcentage = ? WHERE id = ?');
            $progressId = (int) $existing['id'];
            $stmt->bind_param('iii', $pagesLues, $pourcentage, $progressId);
        } else {
            $stmt = $mysqli->prepare('INSERT INTO progress (book_id, user_id, pages_lues, pourcentage) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('iiii', $bookId, $userId, $pagesLues, $pourcentage);
        }
        $stmt->execute();
        $stmt->close();

        header('Location: /club-lecture/pages/livres/voir.php?id=' . $bookId . '&success=progress_saved');
        exit;
    }

    if ($action === 'toggle_session') {
        $sessionId = (int) ($_POST['session_id'] ?? 0);
        $want = $_POST['want'] ?? 'inscrire';

        $sessionCheck = $mysqli->prepare('SELECT id FROM sessions WHERE id = ? AND book_id = ? LIMIT 1');
        $sessionCheck->bind_param('ii', $sessionId, $bookId);
        $sessionCheck->execute();
        $sessionExists = $sessionCheck->get_result()->fetch_assoc();
        $sessionCheck->close();

        if ($sessionExists) {
            if ($want === 'desinscrire') {
                $stmt = $mysqli->prepare('DELETE FROM session_attendance WHERE session_id = ? AND user_id = ?');
                $stmt->bind_param('ii', $sessionId, $userId);
                $stmt->execute();
                $stmt->close();
            } else {
                $check = $mysqli->prepare('SELECT id FROM session_attendance WHERE session_id = ? AND user_id = ? LIMIT 1');
                $check->bind_param('ii', $sessionId, $userId);
                $check->execute();
                $exists = $check->get_result()->fetch_assoc();
                $check->close();

                if (!$exists) {
                    $stmt = $mysqli->prepare('INSERT INTO session_attendance (session_id, user_id, statut) VALUES (?, ?, "inscrit")');
                    $stmt->bind_param('ii', $sessionId, $userId);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        header('Location: /club-lecture/pages/livres/voir.php?id=' . $bookId . '&success=session_updated');
        exit;
    }
}

$stmt = $mysqli->prepare('SELECT * FROM books WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $bookId);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    die('Livre introuvable.');
}

$documents = [];
$docRes = $mysqli->query('SELECT d.id, d.filename, d.mime, d.size, d.uploaded_at, u.nom AS uploader_nom
                          FROM documents d
                          LEFT JOIN users u ON u.id = d.uploaded_by
                          WHERE d.book_id = ' . $bookId . '
                          ORDER BY d.uploaded_at DESC');
if ($docRes) {
    while ($row = $docRes->fetch_assoc()) {
        $documents[] = $row;
    }
}

$visibleReviews = [];
$revStmt = $mysqli->prepare('SELECT r.id, r.note, r.commentaire, r.created_at, u.nom
                             FROM reviews r
                             INNER JOIN users u ON u.id = r.user_id
                             WHERE r.book_id = ? AND r.masque = 0
                             ORDER BY r.created_at DESC');
$revStmt->bind_param('i', $bookId);
$revStmt->execute();
$revRes = $revStmt->get_result();
while ($r = $revRes->fetch_assoc()) {
    $visibleReviews[] = $r;
}
$revStmt->close();

$myReview = null;
$myReviewStmt = $mysqli->prepare('SELECT id, note, commentaire, masque FROM reviews WHERE book_id = ? AND user_id = ? LIMIT 1');
$myReviewStmt->bind_param('ii', $bookId, $userId);
$myReviewStmt->execute();
$myReview = $myReviewStmt->get_result()->fetch_assoc();
$myReviewStmt->close();

$myProgress = 0;
$myPagesLues = 0;
$myProgressStmt = $mysqli->prepare('SELECT pages_lues, pourcentage FROM progress WHERE book_id = ? AND user_id = ? LIMIT 1');
$myProgressStmt->bind_param('ii', $bookId, $userId);
$myProgressStmt->execute();
$progressRow = $myProgressStmt->get_result()->fetch_assoc();
if ($progressRow) {
    $myPagesLues = (int) ($progressRow['pages_lues'] ?? 0);
    $myProgress = (int) ($progressRow['pourcentage'] ?? 0);
}
$myProgressStmt->close();

$sessions = [];
$sessStmt = $mysqli->prepare('SELECT id, titre, date_heure, lien, lieu, description FROM sessions WHERE book_id = ? ORDER BY date_heure ASC');
$sessStmt->bind_param('i', $bookId);
$sessStmt->execute();
$sessRes = $sessStmt->get_result();
while ($s = $sessRes->fetch_assoc()) {
    $sessions[] = $s;
}
$sessStmt->close();

$myAttendance = [];
$attStmt = $mysqli->prepare('SELECT session_id, statut FROM session_attendance WHERE user_id = ?');
$attStmt->bind_param('i', $userId);
$attStmt->execute();
$attRes = $attStmt->get_result();
while ($a = $attRes->fetch_assoc()) {
    $myAttendance[(int) $a['session_id']] = $a['statut'];
}
$attStmt->close();

$successCode = $_GET['success'] ?? '';

define('APP_NAME', $book['titre']);
require_once __DIR__ . '/../../inclusions/header.php';
?>

        <h1><?= htmlspecialchars($book['titre']) ?></h1>
        <p><strong>Auteur :</strong> <?= htmlspecialchars($book['auteur']) ?></p>
        <?php if (!empty($book['cover_path'])): ?>
            <p>
                <img class="book-cover-large" src="/club-lecture/pages/livres/couverture.php?id=<?= (int) $book['id'] ?>" alt="Cover de <?= htmlspecialchars($book['titre']) ?>">
            </p>
        <?php endif; ?>
        <p><strong>Nombre total de pages :</strong> <?= (int) ($book['total_pages'] ?? 0) ?></p>
        <p><strong>Description :</strong><br><?= nl2br(htmlspecialchars($book['description'] ?? 'Aucune description.')) ?></p>
        <p><a href="liste.php">Retour a la liste</a></p>

        <?php if ($successCode !== ''): ?>
            <p class="flash-success">Action enregistree avec succes.</p>
        <?php endif; ?>

        <hr>

        <h2>Documents</h2>
        <?php if (isModerator()): ?>
            <form method="post" action="/club-lecture/pages/documents/upload.php" enctype="multipart/form-data">
                <?= csrfInput() ?>
                <input type="hidden" name="book_id" value="<?= $bookId ?>">
                <label>Ajouter un document (PDF/JPG/PNG, max 5MB)</label><br>
                <input type="file" name="document" required>
                <button type="submit">Uploader</button>
            </form>
        <?php endif; ?>

        <?php if (!$documents): ?>
            <p>Aucun document pour ce livre.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($documents as $doc): ?>
                    <li>
                        <?php $docMime = (string) ($doc['mime'] ?? ''); ?>
                        <?php if ($docMime === 'application/pdf'): ?>
                            <a href="/club-lecture/pages/livres/lire.php?id=<?= $bookId ?>&doc_id=<?= (int) $doc['id'] ?>">Lire</a>
                        <?php else: ?>
                            <a href="/club-lecture/pages/documents/telecharger.php?id=<?= (int) $doc['id'] ?>" target="_blank" rel="noopener">Ouvrir</a>
                        <?php endif; ?>
                        | <a href="/club-lecture/pages/documents/telecharger.php?id=<?= (int) $doc['id'] ?>&download=1">Telecharger</a>
                        - <?= htmlspecialchars($doc['filename']) ?>
                        (<?= htmlspecialchars((string) ($doc['mime'] ?? 'fichier')) ?>, <?= (int) round(((int) $doc['size']) / 1024) ?> Ko)
                        <?php if (isModerator()): ?>
                            <form method="post" action="/club-lecture/pages/documents/delete.php" class="action-inline-form" data-confirm="Supprimer ce document ?">
                                <?= csrfInput() ?>
                                <input type="hidden" name="id" value="<?= (int) $doc['id'] ?>">
                                <input type="hidden" name="book_id" value="<?= $bookId ?>">
                                <button type="submit" class="btn-danger">Supprimer</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h2>Avis des membres</h2>
        <form method="post">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="save_review">

            <label>Ma note</label><br>
            <?php $currentNote = (int) ($myReview['note'] ?? 0); ?>
            <fieldset class="rating-stars" aria-label="Ma note">
                <input type="radio" id="note5" name="note" value="5" <?= $currentNote === 5 ? 'checked' : '' ?> required>
                <label for="note5" title="5 sur 5">&#9733;</label>

                <input type="radio" id="note4" name="note" value="4" <?= $currentNote === 4 ? 'checked' : '' ?> required>
                <label for="note4" title="4 sur 5">&#9733;</label>

                <input type="radio" id="note3" name="note" value="3" <?= $currentNote === 3 ? 'checked' : '' ?> required>
                <label for="note3" title="3 sur 5">&#9733;</label>

                <input type="radio" id="note2" name="note" value="2" <?= $currentNote === 2 ? 'checked' : '' ?> required>
                <label for="note2" title="2 sur 5">&#9733;</label>

                <input type="radio" id="note1" name="note" value="1" <?= $currentNote === 1 ? 'checked' : '' ?> required>
                <label for="note1" title="1 sur 5">&#9733;</label>
            </fieldset><br>

            <label>Mon commentaire</label><br>
            <textarea name="commentaire" rows="4"><?= htmlspecialchars($myReview['commentaire'] ?? '') ?></textarea><br><br>

            <button type="submit">Enregistrer mon avis</button>
            <?php if ($myReview && (int) ($myReview['masque'] ?? 0) === 1): ?>
                <p class="flash-errors">Ton avis est masque par moderation.</p>
            <?php endif; ?>
        </form>

        <?php if (!$visibleReviews): ?>
            <p>Aucun avis visible pour le moment.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($visibleReviews as $review): ?>
                    <li>
                        <?php
                            $note = (int) ($review['note'] ?? 0);
                            if ($note < 0) {
                                $note = 0;
                            }
                            if ($note > 5) {
                                $note = 5;
                            }
                            $starsOn = str_repeat('&#9733;', $note);
                            $starsOff = str_repeat('&#9734;', 5 - $note);
                        ?>
                        <strong><?= htmlspecialchars($review['nom']) ?></strong>
                        (note: <span class="review-stars"><?= $starsOn . $starsOff ?></span>)<br>
                        <?= nl2br(htmlspecialchars($review['commentaire'] ?? '')) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h2>Ma progression</h2>
        <?php $bookTotalPages = (int) ($book['total_pages'] ?? 0); ?>
        <?php if ($bookTotalPages <= 0): ?>
            <p class="flash-errors">Le nombre total de pages de ce livre n'est pas defini.</p>
        <?php else: ?>
            <p>Ta progression: <?= $myProgress ?>% (<?= $myPagesLues ?> / <?= $bookTotalPages ?> pages)</p>
        <?php endif; ?>

        <h2>Sessions de discussion</h2>
        <?php if (isModerator()): ?>
            <p><a href="/club-lecture/pages/administration/sessions.php?book_id=<?= $bookId ?>">Gerer les sessions de ce livre</a></p>
        <?php endif; ?>

        <?php if (!$sessions): ?>
            <p>Aucune session planifiee pour ce livre.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($sessions as $session): ?>
                    <?php $status = $myAttendance[(int) $session['id']] ?? null; ?>
                    <li>
                        <strong><?= htmlspecialchars($session['titre']) ?></strong>
                        - <?= htmlspecialchars($session['date_heure']) ?><br>
                        <?php if (!empty($session['lieu'])): ?>Lieu: <?= htmlspecialchars($session['lieu']) ?><br><?php endif; ?>
                        <?php if (!empty($session['lien'])): ?>Lien: <a href="<?= htmlspecialchars($session['lien']) ?>" target="_blank" rel="noopener">ouvrir</a><br><?php endif; ?>
                        <?php if (!empty($session['description'])): ?><?= nl2br(htmlspecialchars($session['description'])) ?><br><?php endif; ?>

                        <form method="post" class="action-inline-form">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action" value="toggle_session">
                            <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                            <?php if ($status): ?>
                                <input type="hidden" name="want" value="desinscrire">
                                <button type="submit" class="btn-danger">Me desinscrire</button>
                                <span>Statut: <?= htmlspecialchars($status) ?></span>
                            <?php else: ?>
                                <input type="hidden" name="want" value="inscrire">
                                <button type="submit">M'inscrire</button>
                            <?php endif; ?>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
<?php require_once __DIR__ . '/../../inclusions/footer.php'; ?>

