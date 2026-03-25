<?php
session_start();
require_once __DIR__ . '/../../includes/database.php';

// Vérification de connexion basique
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$role = $_SESSION['role'] ?? 'membre';

// Récupérer tous les livres
$query = "SELECT id, titre, auteur, date_debut, date_fin FROM books ORDER BY date_debut DESC";
$result = mysqli_query($mysqli, $query);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des lectures</title>
</head>
<body>
    <h1>Lectures du Club</h1>
    
    <a href="../dashboard.php">Retour au Dashboard</a> | 
    <?php if ($role === 'admin' || $role === 'moderateur'): ?>
        <a href="add.php"><button>Ajouter un livre</button></a>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Auteur</th>
                <th>Date de début</th>
                <th>Date de fin</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($book = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= htmlspecialchars($book['titre']) ?></td>
                    <td><?= htmlspecialchars($book['auteur']) ?></td>
                    <td><?= htmlspecialchars($book['date_debut'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($book['date_fin'] ?? 'N/A') ?></td>
                    <td>
                        <a href="view.php?id=<?= $book['id'] ?>">Voir</a>
                        
                        <?php if ($role === 'admin' || $role === 'moderateur'): ?>
                            | <a href="edit.php?id=<?= $book['id'] ?>">Modifier</a>
                        <?php endif; ?>

                        <?php if ($role === 'admin'): ?>
                            | <a href="delete.php?id=<?= $book['id'] ?>" onclick="return confirm('Sûr de vouloir supprimer ce livre ?');">Supprimer</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>