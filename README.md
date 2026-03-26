# Club-Lecture

Application PHP/MySQL pour gerer un club de lecture.

## Prerequis

- WAMP (Apache + MySQL + PHP)
- PHP 8+ recommande
- MySQL/MariaDB actif

## Installation

1. Cloner le projet dans `c:/wamp64/www/club-lecture`.
2. Demarrer WAMP (Apache + MySQL).
3. Importer la base:
	 - Ouvrir phpMyAdmin
	 - Creer/importer via le fichier `db.sql`
4. Verifier la config DB dans `includes/database.php`:
	 - host: `localhost`
	 - user: `root`
	 - password: `''` (ou ton mot de passe local)
	 - dbname: `club_lecture`
5. Ouvrir: `http://localhost/club-lecture/`

## Compte admin (creation)

Option rapide via SQL:

1. Créer un hash PHP (exemple en local):
	 - `php -r "echo password_hash('Admin123!', PASSWORD_DEFAULT);"`
2. Inserer l'utilisateur dans `users` avec `role = 'admin'` et `statut = 'actif'`.

Exemple SQL:

```sql
INSERT INTO users (nom, email, password_hash, role, statut)
VALUES ('Admin', 'admin@club.local', 'COLLER_LE_HASH_ICI', 'admin', 'actif');
```

## Roles

- `membre`:
	- consulter livres
	- ajouter/modifier son avis
	- mettre sa progression
	- s'inscrire/desinscrire aux sessions
- `moderateur`:
	- tout membre
	- ajouter/modifier livres
	- uploader/supprimer documents
	- moderer avis
	- gerer sessions
- `admin`:
	- tout moderateur
	- gestion utilisateurs
	- suppression livres

## Modules implementes

- Auth: login/register/logout
- Livres: add/edit/delete/list/view
- Documents: upload/download/delete
- Avis: creation/edition membre + moderation admin/modo
- Progression: mise a jour pourcentage par membre
- Sessions:
	- CRUD admin/modo
	- liste membre des sessions a venir
	- inscription/desinscription
	- statut inscrit/present/absent

## Securite

- Verification utilisateur connecte via `requireLogin()`
- Controle role via `restrictToAdmin()` / `restrictToModerator()`
- Protection CSRF sur actions sensibles POST
- Dossier `uploads/` protege (`.htaccess` + index)

## Fichiers importants

- `includes/auth.php`: middleware auth/roles
- `includes/functions.php`: helpers + CSRF
- `pages/books/view.php`: documents/avis/progression/sessions sur un livre
- `pages/admin/avis.php`: moderation avis
- `pages/admin/sessions.php`: gestion sessions
- `pages/sessions/list.php`: sessions a venir cote membre