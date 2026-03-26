<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$userId = (int) ($_SESSION['user_id'] ?? 0);
$bookIdFilter = (int) ($_GET['book_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrFail();

    $action = $_POST['action'] ?? '';
    $sessionId = (int) ($_POST['session_id'] ?? 0);

    if ($sessionId > 0) {
        if ($action === 'unsubscribe') {
            $stmt = $mysqli->prepare('DELETE FROM session_attendance WHERE session_id = ? AND user_id = ?');
            $stmt->bind_param('ii', $sessionId, $userId);
            $stmt->execute();
            $stmt->close();
        }

        if ($action === 'subscribe') {
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

    $redirect = '/club-lecture/pages/sessions/list.php';
    if ($bookIdFilter > 0) {
        $redirect .= '?book_id=' . $bookIdFilter;
    }
    header('Location: ' . $redirect);
    exit;
}

$books = [];
$bookRes = $mysqli->query('SELECT id, titre FROM books ORDER BY titre ASC');
if ($bookRes) {
    while ($row = $bookRes->fetch_assoc()) {
        $books[] = $row;
    }
}

$sql = 'SELECT s.id, s.titre, s.date_heure, s.lieu, s.lien, s.description,
               b.id AS book_id, b.titre AS book_titre
        FROM sessions s
        INNER JOIN books b ON b.id = s.book_id
        WHERE s.date_heure >= NOW()';
if ($bookIdFilter > 0) {
    $sql .= ' AND s.book_id = ' . $bookIdFilter;
}
$sql .= ' ORDER BY s.date_heure ASC';

$sessions = [];
$sessRes = $mysqli->query($sql);
if ($sessRes) {
    while ($row = $sessRes->fetch_assoc()) {
        $sessions[] = $row;
    }
}

$attendanceMap = [];
$attStmt = $mysqli->prepare('SELECT session_id, statut FROM session_attendance WHERE user_id = ?');
$attStmt->bind_param('i', $userId);
$attStmt->execute();
$attRes = $attStmt->get_result();
while ($row = $attRes->fetch_assoc()) {
    $attendanceMap[(int) $row['session_id']] = $row['statut'];
}
$attStmt->close();

define('APP_NAME', 'Sessions');
require_once __DIR__ . '/../../includes/header.php';
?>

<h1>Sessions a venir</h1>

<form method="get">
  <label>Filtrer par livre</label><br>
  <select name="book_id">
    <option value="0">Tous les livres</option>
    <?php foreach ($books as $book): ?>
      <option value="<?= (int) $book['id'] ?>" <?= $bookIdFilter === (int) $book['id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($book['titre']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <button type="submit">Filtrer</button>
</form>

<?php if (!$sessions): ?>
  <p>Aucune session a venir.</p>
<?php else: ?>
  <ul>
    <?php foreach ($sessions as $session): ?>
      <?php $status = $attendanceMap[(int) $session['id']] ?? null; ?>
      <li>
        <strong><?= htmlspecialchars($session['titre']) ?></strong>
        - <?= htmlspecialchars($session['date_heure']) ?>
        (Livre: <a href="/club-lecture/pages/books/view.php?id=<?= (int) $session['book_id'] ?>"><?= htmlspecialchars($session['book_titre']) ?></a>)
        <br>
        <?php if (!empty($session['lieu'])): ?>Lieu: <?= htmlspecialchars($session['lieu']) ?><br><?php endif; ?>
        <?php if (!empty($session['lien'])): ?>Lien: <a href="<?= htmlspecialchars($session['lien']) ?>" target="_blank" rel="noopener">ouvrir</a><br><?php endif; ?>

        <form method="post" class="action-inline-form">
          <?= csrfInput() ?>
          <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
          <?php if ($status): ?>
            <input type="hidden" name="action" value="unsubscribe">
            <button type="submit" class="btn-danger">Me desinscrire</button>
            <span>Statut: <?= htmlspecialchars($status) ?></span>
          <?php else: ?>
            <input type="hidden" name="action" value="subscribe">
            <button type="submit">M'inscrire</button>
          <?php endif; ?>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
