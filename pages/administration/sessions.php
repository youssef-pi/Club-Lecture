<?php
require_once __DIR__ . '/../../inclusions/auth.php';
requireLogin();
restrictToModerator();

$errors = [];
$success = '';

$bookIdFilter = (int) ($_GET['book_id'] ?? 0);
$editId = (int) ($_GET['edit'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrFail();

    $action = $_POST['action'] ?? '';

    if ($action === 'save_session') {
        $sessionId = (int) ($_POST['session_id'] ?? 0);
        $bookId = (int) ($_POST['book_id'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');
        $dateHeure = trim($_POST['date_heure'] ?? '');
        $lien = trim($_POST['lien'] ?? '');
        $lieu = trim($_POST['lieu'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($bookId <= 0 || $titre === '' || $dateHeure === '') {
            $errors[] = 'Livre, titre et date/heure sont obligatoires.';
        } else {
            if ($sessionId > 0) {
                $stmt = $mysqli->prepare('UPDATE sessions SET book_id = ?, titre = ?, date_heure = ?, lien = ?, lieu = ?, description = ? WHERE id = ?');
                $stmt->bind_param('isssssi', $bookId, $titre, $dateHeure, $lien, $lieu, $description, $sessionId);
                if ($stmt->execute()) {
                    $success = 'Session modifiee.';
                } else {
                    $errors[] = 'Erreur modification session.';
                }
                $stmt->close();
            } else {
                $createdBy = (int) ($_SESSION['user_id'] ?? 0);
                $stmt = $mysqli->prepare('INSERT INTO sessions (book_id, titre, date_heure, lien, lieu, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('isssssi', $bookId, $titre, $dateHeure, $lien, $lieu, $description, $createdBy);
                if ($stmt->execute()) {
                    $success = 'Session ajoutee.';
                } else {
                    $errors[] = 'Erreur ajout session.';
                }
                $stmt->close();
            }
        }

        if (!$errors) {
            header('Location: /club-lecture/pages/administration/sessions.php?book_id=' . $bookId);
            exit;
        }
    }

    if ($action === 'delete_session') {
        $sessionId = (int) ($_POST['session_id'] ?? 0);
        $bookId = (int) ($_POST['book_id'] ?? 0);

        if ($sessionId > 0) {
            $stmt = $mysqli->prepare('DELETE FROM sessions WHERE id = ?');
            $stmt->bind_param('i', $sessionId);
            $stmt->execute();
            $stmt->close();
            $success = 'Session supprimee.';
        }

        header('Location: /club-lecture/pages/administration/sessions.php?book_id=' . $bookId);
        exit;
    }

    if ($action === 'update_attendance') {
        $attendanceId = (int) ($_POST['attendance_id'] ?? 0);
        $statut = $_POST['statut'] ?? 'inscrit';
        $bookId = (int) ($_POST['book_id'] ?? 0);
        $allowed = ['inscrit', 'present', 'absent'];

        if ($attendanceId > 0 && in_array($statut, $allowed, true)) {
            $stmt = $mysqli->prepare('UPDATE session_attendance SET statut = ? WHERE id = ?');
            $stmt->bind_param('si', $statut, $attendanceId);
            $stmt->execute();
            $stmt->close();
        }

        header('Location: /club-lecture/pages/administration/sessions.php?book_id=' . $bookId);
        exit;
    }
}

$books = [];
$booksRes = $mysqli->query('SELECT id, titre FROM books ORDER BY titre ASC');
if ($booksRes) {
    while ($b = $booksRes->fetch_assoc()) {
        $books[] = $b;
    }
}

$editSession = null;
if ($editId > 0) {
    $stmt = $mysqli->prepare('SELECT * FROM sessions WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editSession = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$sessions = [];
if ($bookIdFilter > 0) {
  $stmt = $mysqli->prepare('SELECT s.*, b.titre AS book_titre
                FROM sessions s
                INNER JOIN books b ON b.id = s.book_id
                WHERE s.book_id = ?
                ORDER BY s.date_heure ASC');
  $stmt->bind_param('i', $bookIdFilter);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $sessions[] = $row;
  }
  $stmt->close();
} else {
  $res = $mysqli->query('SELECT s.*, b.titre AS book_titre
               FROM sessions s
               INNER JOIN books b ON b.id = s.book_id
               ORDER BY s.date_heure ASC');
  if ($res) {
    while ($row = $res->fetch_assoc()) {
      $sessions[] = $row;
    }
  }
}

$attendanceRows = [];
if ($bookIdFilter > 0) {
  $stmt = $mysqli->prepare('SELECT sa.id, sa.statut, sa.created_at,
                   s.id AS session_id, s.titre AS session_titre,
                   u.nom AS user_nom
                FROM session_attendance sa
                INNER JOIN sessions s ON s.id = sa.session_id
                INNER JOIN users u ON u.id = sa.user_id
                WHERE s.book_id = ?
                ORDER BY s.date_heure ASC, u.nom ASC');
  $stmt->bind_param('i', $bookIdFilter);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $attendanceRows[] = $row;
  }
  $stmt->close();
} else {
  $res = $mysqli->query('SELECT sa.id, sa.statut, sa.created_at,
                  s.id AS session_id, s.titre AS session_titre,
                  u.nom AS user_nom
               FROM session_attendance sa
               INNER JOIN sessions s ON s.id = sa.session_id
               INNER JOIN users u ON u.id = sa.user_id
               ORDER BY s.date_heure ASC, u.nom ASC');
  if ($res) {
    while ($row = $res->fetch_assoc()) {
      $attendanceRows[] = $row;
    }
  }
}

define('APP_NAME', 'Admin - Gestion des sessions');
require_once __DIR__ . '/../../inclusions/header.php';
?>

    <h1>Gestion des sessions</h1>
    <p><a href="/club-lecture/index.php">Retour a l'accueil</a></p>

    <?php if ($success): ?>
      <p class="flash-success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <?php if ($errors): ?>
      <ul class="flash-errors">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <section>
      <h2>Filtrer par livre</h2>
      <form method="get">
        <label>Livre</label><br>
        <select name="book_id" required>
          <option value="">Choisir</option>
          <?php foreach ($books as $book): ?>
            <option value="<?= (int) $book['id'] ?>" <?= $bookIdFilter === (int) $book['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($book['titre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="submit">Afficher</button>
      </form>
    </section>

    <section>
      <h2><?= $editSession ? 'Modifier une session' : 'Ajouter une session' ?></h2>
      <form method="post">
        <?= csrfInput() ?>
        <input type="hidden" name="action" value="save_session">
        <input type="hidden" name="session_id" value="<?= (int) ($editSession['id'] ?? 0) ?>">

        <label>Livre</label><br>
        <select name="book_id" required>
          <option value="">Choisir</option>
          <?php foreach ($books as $book): ?>
            <?php $selectedBookId = (int) ($editSession['book_id'] ?? $bookIdFilter); ?>
            <option value="<?= (int) $book['id'] ?>" <?= $selectedBookId === (int) $book['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($book['titre']) ?>
            </option>
          <?php endforeach; ?>
        </select><br><br>

        <label>Titre</label><br>
        <input type="text" name="titre" value="<?= htmlspecialchars($editSession['titre'] ?? '') ?>" required><br><br>

        <label>Date et heure</label><br>
        <input type="datetime-local" name="date_heure" value="<?= htmlspecialchars(isset($editSession['date_heure']) ? date('Y-m-d\\TH:i', strtotime($editSession['date_heure'])) : '') ?>" required><br><br>

        <label>Lien (optionnel)</label><br>
        <input type="text" name="lien" value="<?= htmlspecialchars($editSession['lien'] ?? '') ?>"><br><br>

        <label>Lieu (optionnel)</label><br>
        <input type="text" name="lieu" value="<?= htmlspecialchars($editSession['lieu'] ?? '') ?>"><br><br>

        <label>Description</label><br>
        <textarea name="description" rows="3"><?= htmlspecialchars($editSession['description'] ?? '') ?></textarea><br><br>

        <button type="submit">Enregistrer</button>
      </form>
    </section>

    <section>
      <h2>Liste des sessions</h2>
      <?php if (!$sessions): ?>
        <p>Aucune session trouvee.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($sessions as $session): ?>
            <li>
              <strong><?= htmlspecialchars($session['titre']) ?></strong>
              - livre: <?= htmlspecialchars($session['book_titre']) ?>
              - <?= htmlspecialchars($session['date_heure']) ?>
              <?php if (!empty($session['lieu'])): ?> (<?= htmlspecialchars($session['lieu']) ?>)<?php endif; ?>
              <br>
              <a href="/club-lecture/pages/administration/sessions.php?book_id=<?= (int) $session['book_id'] ?>&edit=<?= (int) $session['id'] ?>">Modifier</a>

              <form method="post" class="action-inline-form" data-confirm="Supprimer cette session ?">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="delete_session">
                <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                <input type="hidden" name="book_id" value="<?= (int) $session['book_id'] ?>">
                <button type="submit" class="btn-danger">Supprimer</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section>
      <h2>Inscriptions et presence</h2>
      <?php if (!$attendanceRows): ?>
        <p>Aucune inscription trouvee.</p>
      <?php else: ?>
        <div class="users-table-wrapper">
          <table class="users-table">
            <thead>
              <tr>
                <th>Session</th>
                <th>Membre</th>
                <th>Statut</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($attendanceRows as $row): ?>
                <tr>
                  <td><?= htmlspecialchars($row['session_titre']) ?></td>
                  <td><?= htmlspecialchars($row['user_nom']) ?></td>
                  <td><?= htmlspecialchars($row['statut']) ?></td>
                  <td>
                    <form method="post" class="action-inline-form">
                      <?= csrfInput() ?>
                      <input type="hidden" name="action" value="update_attendance">
                      <input type="hidden" name="attendance_id" value="<?= (int) $row['id'] ?>">
                      <input type="hidden" name="book_id" value="<?= (int) $bookIdFilter ?>">
                      <select name="statut">
                        <option value="inscrit" <?= $row['statut'] === 'inscrit' ? 'selected' : '' ?>>inscrit</option>
                        <option value="present" <?= $row['statut'] === 'present' ? 'selected' : '' ?>>present</option>
                        <option value="absent" <?= $row['statut'] === 'absent' ? 'selected' : '' ?>>absent</option>
                      </select>
                      <button type="submit">Mettre a jour</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
<?php require_once __DIR__ . '/../../inclusions/footer.php'; ?>

