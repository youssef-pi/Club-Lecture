<?php
require_once __DIR__ . '/../../includes/auth.php';
restrictToAdmin();

$errors = [];
$success = '';

$allowedRoles = ['admin', 'moderateur', 'membre'];
$allowedStatuts = ['actif', 'banni'];

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrfOrFail();

    if ($action === 'add_user') {
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'membre';
        $statut = $_POST['statut'] ?? 'actif';

        if ($nom === '' || mb_strlen($nom) < 2) {
            $errors[] = 'Nom invalide (min 2 caractères).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Mot de passe trop court (min 6 caractères).';
        }
        if (!in_array($role, $allowedRoles, true)) {
            $errors[] = 'Rôle invalide.';
        }
        if (!in_array($statut, $allowedStatuts, true)) {
            $errors[] = 'Statut invalide.';
        }

        if (!$errors) {
            $check = $mysqli->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $check->bind_param('s', $email);
            $check->execute();
            $exists = $check->get_result();

            if ($exists->num_rows > 0) {
                $errors[] = 'Cet email existe déjà.';
            }
            $check->close();

            if (!$errors) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare('INSERT INTO users (nom, email, password_hash, role, statut) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('sssss', $nom, $email, $passwordHash, $role, $statut);

                if ($stmt->execute()) {
                    $success = 'Utilisateur ajouté.';
                } else {
                    $errors[] = 'Erreur ajout utilisateur.';
                }
                $stmt->close();
            }
        }
    }

    if ($action === 'update_user') {
        $id = (int) ($_POST['id'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'membre';
        $statut = $_POST['statut'] ?? 'actif';
        $newPassword = $_POST['new_password'] ?? '';

        if ($id <= 0) {
            $errors[] = 'ID utilisateur invalide.';
        }
        if ($nom === '' || mb_strlen($nom) < 2) {
            $errors[] = 'Nom invalide (min 2 caractères).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        }
        if (!in_array($role, $allowedRoles, true)) {
            $errors[] = 'Rôle invalide.';
        }
        if (!in_array($statut, $allowedStatuts, true)) {
            $errors[] = 'Statut invalide.';
        }
        if ($newPassword !== '' && strlen($newPassword) < 6) {
            $errors[] = 'Nouveau mot de passe trop court (min 6 caractères).';
        }
        if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $id) {
          if ($statut === 'banni') {
            $errors[] = 'Vous ne pouvez pas vous bannir vous-meme.';
          }
          if ($role !== 'admin') {
            $errors[] = 'Vous ne pouvez pas retirer votre propre role admin.';
          }
        }

        if (!$errors) {
            $check = $mysqli->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
            $check->bind_param('si', $email, $id);
            $check->execute();
            $exists = $check->get_result();

            if ($exists->num_rows > 0) {
                $errors[] = 'Cet email est déjà utilisé par un autre utilisateur.';
            }
            $check->close();

            if (!$errors) {
                if ($newPassword !== '') {
                    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $mysqli->prepare('UPDATE users SET nom = ?, email = ?, role = ?, statut = ?, password_hash = ? WHERE id = ?');
                    $stmt->bind_param('sssssi', $nom, $email, $role, $statut, $passwordHash, $id);
                } else {
                    $stmt = $mysqli->prepare('UPDATE users SET nom = ?, email = ?, role = ?, statut = ? WHERE id = ?');
                    $stmt->bind_param('ssssi', $nom, $email, $role, $statut, $id);
                }

                if ($stmt->execute()) {
                    $success = 'Utilisateur modifié.';

                    if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $id) {
                        $_SESSION['nom'] = $nom;
                        $_SESSION['email'] = $email;
                        $_SESSION['role'] = $role;
                    }
                } else {
                    $errors[] = 'Erreur modification utilisateur.';
                }
                $stmt->close();
            }
        }
    }

    if ($action === 'delete_user') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $errors[] = 'ID utilisateur invalide.';
        } elseif (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $id) {
            $errors[] = 'Vous ne pouvez pas supprimer votre propre compte.';
        } else {
            $stmt = $mysqli->prepare('DELETE FROM users WHERE id = ?');
            $stmt->bind_param('i', $id);

            if ($stmt->execute()) {
                $success = 'Utilisateur supprimé.';
            } else {
                $errors[] = 'Erreur suppression utilisateur.';
            }
            $stmt->close();
        }
    }
}

$users = [];
$result = $mysqli->query('SELECT id, nom, email, role, statut, created_at FROM users ORDER BY id ASC');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - Utilisateurs</title>
  <link rel="stylesheet" href="/club-lecture/pages/style/style.css?v=20260326">
</head>
<body>
  <main>
    <h1>Administration des utilisateurs</h1>
    <p><a href="/club-lecture/index.php">← Retour à l'accueil</a></p>

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
      <h2>Ajouter un utilisateur</h2>
      <form method="post">
        <?= csrfInput() ?>
        <input type="hidden" name="action" value="add_user">

        <label>Nom</label><br>
        <input type="text" name="nom" required><br><br>

        <label>Email</label><br>
        <input type="email" name="email" required><br><br>

        <label>Mot de passe</label><br>
        <input type="password" name="password" required><br><br>

        <label>Rôle</label><br>
        <select name="role">
          <option value="membre">membre</option>
          <option value="moderateur">moderateur</option>
          <option value="admin">admin</option>
        </select><br><br>

        <label>Statut</label><br>
        <select name="statut">
          <option value="actif">actif</option>
          <option value="banni">banni</option>
        </select><br><br>

        <button type="submit">Ajouter</button>
      </form>
    </section>

    <section>
      <h2>Liste des utilisateurs</h2>

      <?php if (!$users): ?>
        <p>Aucun utilisateur trouvé.</p>
      <?php else: ?>
        <div class="users-table-wrapper">
          <table class="users-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Statut</th>
                <th>Nouveau mot de passe</th>
                <th>Créé le</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
                <tr>
                  <form method="post" class="action-inline-form">
                    <?= csrfInput() ?>
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">

                    <td><?= (int) $user['id'] ?></td>
                    <td>
                      <input type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
                    </td>
                    <td>
                      <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </td>
                    <td>
                      <select name="role">
                        <option value="membre" <?= $user['role'] === 'membre' ? 'selected' : '' ?>>membre</option>
                        <option value="moderateur" <?= $user['role'] === 'moderateur' ? 'selected' : '' ?>>moderateur</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
                      </select>
                    </td>
                    <td>
                      <select name="statut">
                        <option value="actif" <?= $user['statut'] === 'actif' ? 'selected' : '' ?>>actif</option>
                        <option value="banni" <?= $user['statut'] === 'banni' ? 'selected' : '' ?>>banni</option>
                      </select>
                    </td>
                    <td>
                      <input type="password" name="new_password" placeholder="optionnel">
                    </td>
                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                    <td class="users-actions-cell">
                        <button type="submit" class="btn-gap">Modifier</button>
                  </form>

                  <form method="post" class="action-inline-form" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                    <?= csrfInput() ?>
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                    <button type="submit" class="btn-danger">Supprimer</button>
                  </form>
                    </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </main>
  <script src="/club-lecture/pages/style/main.js?v=20260326"></script>
</body>
</html>
