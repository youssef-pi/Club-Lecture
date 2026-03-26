<?php
require_once __DIR__ . '/../../includes/auth.php';
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

        header('Location: /club-lecture/pages/books/view.php?id=' . $bookId . '&success=review_saved');
        exit;
    }

    if ($action === 'save_progress') {
        $pourcentage = (int) ($_POST['pourcentage'] ?? 0);
        if ($pourcentage < 0) {
            $pourcentage = 0;
        }
        if ($pourcentage > 100) {
            $pourcentage = 100;
        }

        $check = $mysqli->prepare('SELECT id FROM progress WHERE book_id = ? AND user_id = ? LIMIT 1');
        $check->bind_param('ii', $bookId, $userId);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existing) {
            $stmt = $mysqli->prepare('UPDATE progress SET pourcentage = ? WHERE id = ?');
            $progressId = (int) $existing['id'];
            $stmt->bind_param('ii', $pourcentage, $progressId);
        } else {
            $stmt = $mysqli->prepare('INSERT INTO progress (book_id, user_id, pourcentage) VALUES (?, ?, ?)');
            $stmt->bind_param('iii', $bookId, $userId, $pourcentage);
        }
        $stmt->execute();
        $stmt->close();

        header('Location: /club-lecture/pages/books/view.php?id=' . $bookId . '&success=progress_saved');
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

        header('Location: /club-lecture/pages/books/view.php?id=' . $bookId . '&success=session_updated');
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
$myProgressStmt = $mysqli->prepare('SELECT pourcentage FROM progress WHERE book_id = ? AND user_id = ? LIMIT 1');
$myProgressStmt->bind_param('ii', $bookId, $userId);
$myProgressStmt->execute();
$progressRow = $myProgressStmt->get_result()->fetch_assoc();
if ($progressRow) {
    $myProgress = (int) $progressRow['pourcentage'];
}
$myProgressStmt->close();

$averageProgress = 0;
$avgStmt = $mysqli->prepare('SELECT AVG(pourcentage) AS avg_progress FROM progress WHERE book_id = ?');
$avgStmt->bind_param('i', $bookId);
$avgStmt->execute();
$avgRow = $avgStmt->get_result()->fetch_assoc();
if ($avgRow && $avgRow['avg_progress'] !== null) {
    $averageProgress = (int) round((float) $avgRow['avg_progress']);
}
$avgStmt->close();

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
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($book['titre']) ?></title>
    <link rel="stylesheet" href="/club-lecture/pages/style/style.css?v=20260326">
</head>
<body>
    <header>
        <nav>
            <a href="/club-lecture/index.php">Accueil</a>
            <a href="/club-lecture/pages/books/list.php">Lectures</a>
            <a href="/club-lecture/pages/auth/logout.php">Deconnexion</a>
        </nav>
    </header>

    <main>
        <h1><?= htmlspecialchars($book['titre']) ?></h1>
        <p><strong>Auteur :</strong> <?= htmlspecialchars($book['auteur']) ?></p>
        <p><strong>Periode :</strong> Du <?= htmlspecialchars($book['date_debut'] ?? 'N/A') ?> au <?= htmlspecialchars($book['date_fin'] ?? 'N/A') ?></p>
        <p><strong>Description :</strong><br><?= nl2br(htmlspecialchars($book['description'] ?? 'Aucune description.')) ?></p>
        <p><a href="list.php">Retour a la liste</a></p>

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
                        <a href="/club-lecture/pages/documents/download.php?id=<?= (int) $doc['id'] ?>"><?= htmlspecialchars($doc['filename']) ?></a>
                        (<?= htmlspecialchars((string) ($doc['mime'] ?? 'fichier')) ?>, <?= (int) round(((int) $doc['size']) / 1024) ?> Ko)
                        <?php if (isModerator()): ?>
                            <form method="post" action="/club-lecture/pages/documents/delete.php" class="action-inline-form" onsubmit="return confirm('Supprimer ce document ?');">
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

            <label>Ma note (1-5)</label><br>
            <select name="note" required>
                <?php $currentNote = (int) ($myReview['note'] ?? 0); ?>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>" <?= $currentNote === $i ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select><br><br>

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
                        <strong><?= htmlspecialchars($review['nom']) ?></strong>
                        (note: <?= (int) $review['note'] ?>/5)<br>
                        <?= nl2br(htmlspecialchars($review['commentaire'] ?? '')) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h2>Ma progression</h2>
        <form method="post">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="save_progress">

            <label>Progression en %</label><br>
            <input type="number" name="pourcentage" min="0" max="100" value="<?= $myProgress ?>" required>
            <button type="submit">Mettre a jour</button>
        </form>
        <p>Ta progression: <?= $myProgress ?>%</p>
        <p>Progression moyenne des membres: <?= $averageProgress ?>%</p>

        <h2>Sessions de discussion</h2>
        <?php if (isModerator()): ?>
            <p><a href="/club-lecture/pages/admin/sessions.php?book_id=<?= $bookId ?>">Gerer les sessions de ce livre</a></p>
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
    </main>

    <script src="/club-lecture/pages/style/main.js?v=20260326"></script>
</body>
</html>
