<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
restrictToModerator();

define('APP_NAME', 'Ajouter un livre');
require_once __DIR__ . '/../../includes/header.php';

$errors = [];
$formTitre = '';
$formAuteur = '';
$formDescription = '';
$formDateDebut = '';
$formDateFin = '';

$uploadedCoverAbsPath = null;
$uploadedBookAbsPath = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrFail();

  $titre = trim($_POST['titre'] ?? '');
  $auteur = trim($_POST['auteur'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $date_debut = !empty($_POST['date_debut']) ? $_POST['date_debut'] : null;
  $date_fin = !empty($_POST['date_fin']) ? $_POST['date_fin'] : null;
    $created_by = (int) ($_SESSION['user_id'] ?? 0);

  $formTitre = $titre;
  $formAuteur = $auteur;
  $formDescription = $description;
  $formDateDebut = (string) ($date_debut ?? '');
  $formDateFin = (string) ($date_fin ?? '');

  $coverPath = null;
  $bookFileMeta = null;

    if ($titre === '' || $auteur === '') {
        $errors[] = "Le titre et l'auteur sont obligatoires.";
    }

  if (isset($_FILES['image']) && (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $imageError = (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($imageError !== UPLOAD_ERR_OK) {
      $errors[] = "Erreur lors de l'upload de l'image.";
    } else {
      $maxBytes = 5 * 1024 * 1024;
      $fileSize = (int) ($_FILES['image']['size'] ?? 0);
      if ($fileSize <= 0 || $fileSize > $maxBytes) {
        $errors[] = "Image invalide (max 5 Mo).";
      } else {
        $tmpPath = $_FILES['image']['tmp_name'] ?? '';
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $tmpPath) : '';
        if ($finfo) {
          finfo_close($finfo);
        }

        $allowed = [
          'image/jpeg' => 'jpg',
          'image/png' => 'png',
          'image/webp' => 'webp',
        ];

        if (!isset($allowed[$mime])) {
          $errors[] = "Format d'image non autorise (JPG, PNG, WebP).";
        } else {
          $uploadDir = __DIR__ . '/../../uploads/books';
          if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            $errors[] = "Impossible de creer le dossier d'upload.";
          } else {
            $newName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
            $uploadedCoverAbsPath = $uploadDir . '/' . $newName;
            if (!move_uploaded_file($tmpPath, $uploadedCoverAbsPath)) {
              $errors[] = "Impossible de sauvegarder l'image.";
              $uploadedCoverAbsPath = null;
            } else {
              $coverPath = '/club-lecture/uploads/books/' . $newName;
            }
          }
        }
      }
    }
  }

  if (isset($_FILES['book_file']) && (int) ($_FILES['book_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $bookError = (int) ($_FILES['book_file']['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($bookError !== UPLOAD_ERR_OK) {
      $errors[] = "Erreur lors de l'upload du fichier livre.";
    } else {
      $maxBookBytes = 20 * 1024 * 1024;
      $bookSize = (int) ($_FILES['book_file']['size'] ?? 0);
      if ($bookSize <= 0 || $bookSize > $maxBookBytes) {
        $errors[] = "Fichier livre invalide (PDF, max 20 Mo).";
      } else {
        $bookTmpPath = $_FILES['book_file']['tmp_name'] ?? '';
        $bookOriginalName = trim((string) ($_FILES['book_file']['name'] ?? ''));
        $bookExt = strtolower((string) pathinfo($bookOriginalName, PATHINFO_EXTENSION));

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $bookMime = $finfo ? finfo_file($finfo, $bookTmpPath) : '';
        if ($finfo) {
          finfo_close($finfo);
        }

        if ($bookExt !== 'pdf' || $bookMime !== 'application/pdf') {
          $errors[] = "Le fichier livre doit etre un PDF valide.";
        } else {
          $bookFileMeta = [
            'tmp_name' => $bookTmpPath,
            'original_name' => ($bookOriginalName !== '' ? $bookOriginalName : 'livre.pdf'),
            'mime' => $bookMime,
            'size' => $bookSize,
          ];
        }
      }
    }
  }

  if (!$errors) {
    $mysqli->begin_transaction();

    try {
      $stmt = $mysqli->prepare("INSERT INTO books (titre, auteur, description, cover_path, date_debut, date_fin, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("ssssssi", $titre, $auteur, $description, $coverPath, $date_debut, $date_fin, $created_by);

      if (!$stmt->execute()) {
        throw new Exception("Erreur lors de l'ajout du livre : " . $mysqli->error);
      }
      $stmt->close();

      $bookId = (int) $mysqli->insert_id;

      if ($bookFileMeta) {
        $uploadDir = __DIR__ . '/../../uploads/books';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
          throw new Exception("Impossible de creer le dossier d'upload du livre.");
        }

        $bookStoredName = 'book_' . $bookId . '_' . bin2hex(random_bytes(8)) . '.pdf';
        $uploadedBookAbsPath = $uploadDir . '/' . $bookStoredName;
        $bookRelativePath = 'uploads/books/' . $bookStoredName;

        if (!move_uploaded_file($bookFileMeta['tmp_name'], $uploadedBookAbsPath)) {
          throw new Exception("Impossible de sauvegarder le PDF du livre.");
        }

        $docStmt = $mysqli->prepare('INSERT INTO documents (book_id, filename, filepath, mime, size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)');
        $docFilename = $bookFileMeta['original_name'];
        $docMime = $bookFileMeta['mime'];
        $docSize = (int) $bookFileMeta['size'];
        $docStmt->bind_param('isssii', $bookId, $docFilename, $bookRelativePath, $docMime, $docSize, $created_by);

        if (!$docStmt->execute()) {
          $docStmt->close();
          throw new Exception("Erreur lors de l'ajout du document livre : " . $mysqli->error);
        }
        $docStmt->close();
      }

      $mysqli->commit();
      header('Location: view.php?id=' . $bookId);
      exit;
    } catch (Throwable $e) {
      $mysqli->rollback();

      if ($uploadedCoverAbsPath && file_exists($uploadedCoverAbsPath)) {
        @unlink($uploadedCoverAbsPath);
      }
      if ($uploadedBookAbsPath && file_exists($uploadedBookAbsPath)) {
        @unlink($uploadedBookAbsPath);
      }

      $errors[] = $e->getMessage();
    }
  }
}
?>

<h1>Ajouter une nouvelle lecture</h1>

<?php if ($errors): ?>
  <ul class="flash-errors">
    <?php foreach ($errors as $e): ?>
      <li><?= htmlspecialchars($e) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?= csrfInput() ?>

  <label>Titre :</label><br>
  <input type="text" name="titre" value="<?= htmlspecialchars($formTitre) ?>" required><br><br>

  <label>Auteur :</label><br>
  <input type="text" name="auteur" value="<?= htmlspecialchars($formAuteur) ?>" required><br><br>

  <label>Description :</label><br>
  <textarea name="description" rows="4"><?= htmlspecialchars($formDescription) ?></textarea><br><br>

  <label>Cover (JPG, PNG, WebP - max 5 Mo) :</label><br>
  <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"><br><br>

  <label>Fichier livre (PDF - max 20 Mo) :</label><br>
  <input type="file" name="book_file" accept=".pdf"><br>
  <small>Optionnel : ce fichier sera visible pour les membres dans la section Documents du livre.</small><br><br>

  <label>Date de debut :</label><br>
  <input type="date" name="date_debut" value="<?= htmlspecialchars($formDateDebut) ?>"><br><br>

  <label>Date de fin :</label><br>
  <input type="date" name="date_fin" value="<?= htmlspecialchars($formDateFin) ?>"><br><br>

  <button type="submit">Enregistrer le livre</button>
  <a href="list.php">Annuler</a>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
