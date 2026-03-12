# SecureEvents

Projet de classe — application Symfony dockerisée. Gestion d'événements avec visibilité et droits selon le rôle (anonyme, utilisateur connecté, administrateur).

---

## Prérequis

- Docker et Docker Compose
- Pour les commandes `make`, exécuter depuis le répertoire **SecureEvents/**.

---

## Commandes Make

Toutes les commandes ci-dessous se lancent depuis le répertoire du projet Symfony (contenant le `Makefile`).

| Commande | Description |
|----------|-------------|
| `make` ou `make help` | Affiche la liste des commandes disponibles |
| `make up` | Démarre les conteneurs (app, base de données, mailer) en arrière-plan |
| `make down` | Arrête tous les conteneurs du projet |
| `make build` | Construire l’image Docker de l’application (sans démarrer les conteneurs) |
| `make rebuild` | Reconstruire l’image sans cache puis redémarrer les conteneurs |
| `make install` | Exécute `composer install` dans le conteneur (installation des dépendances PHP) |
| `make migrate` | Exécute les migrations Doctrine (mise à jour du schéma de la base) |
| `make migration-generate` | Génère une nouvelle migration à partir des entités (diff avec la base) |
| `make schema-update` | Met à jour le schéma de la base à partir des entités (sans utiliser les migrations) |
| `make cache-clear` | Vide le cache Symfony |
| `make console CMD="..."` | Lance une commande Symfony (ex. `make console CMD="list"` ou `make console CMD="doctrine:migrations:status"`) |
| `make db-reset` | Réinitialise la BDD (drop, create, migrate) et crée un utilisateur admin (options : `EMAIL=`, `PASSWORD=`, `FIRST=`, `LAST=`) |
| `make seed-events` | Crée des événements de test (option : `COUNT=40`) |
| `make seed-demo` | Crée des utilisateurs de démo (1 admin + 5 users, droits différents) et des événements cohérents (certains users n'ont créé aucun event) ; options : `EVENTS=30`, `force=1` |
| `make logs` | Affiche les logs du service app en continu |
| `make shell` | Ouvre un shell dans le conteneur de l’application |
| `make test` | Lance les tests PHPUnit |

---

## Démarrage rapide

```bash
cd SecureEvents
make up
make install
```

## Arrêt rapide

```bash
make down
```

L’application est accessible sur **http://localhost:8000**.

Pour réinitialiser la base puis créer plusieurs utilisateurs (rôles différents) et des événements cohérents : `make db-reset` puis `make seed-demo`. Pour uniquement des événements (en gardant les users existants) : `make seed-events COUNT=25`.

---

## Sécurité et visibilité des événements

- **Non connecté** : voit uniquement les événements **publiés** ; ne peut pas créer ni éditer d'événements (boutons masqués, accès aux URLs redirigé vers le login).
- **Utilisateur connecté (non admin)** : voit les événements **publiés** et les événements **qu'il a créés** (publiés ou non) ; peut créer des événements ; peut éditer/supprimer **uniquement** ceux dont il est le créateur.
- **Administrateur** : voit **tous** les événements ; peut créer, éditer et supprimer n'importe quel événement.

Chaque événement affiche son **créateur** (prénom, nom, email) sur la liste et sur la page détail. La création et l'édition sont protégées par `security.yaml` (ROLE_USER) et par le `EventVoter` (droits EVENT_VIEW, EVENT_EDIT, EVENT_DELETE).

---

## Gestion des entités et migrations

### 1. Créer les entités

Les entités sont créées avec le composant Maker. Depuis le répertoire du projet (conteneurs démarrés avec `make up`) :

```bash
# Entité User (email, password, roles, firstName, lastName, createdAt)
make console CMD="make:entity User"

# Entité Event (title, description, startDate, location, maxCapacity, isPublished, createdBy → User)
make console CMD="make:entity Event"

# Entité Reservation (participant → User, event → Event, createdAt)
make console CMD="make:entity Reservation"
```

### 2. Générer la migration

Une fois les entités créées ou modifiées :

```bash
make console CMD="doctrine:migrations:diff"
```

Ou, si la cible est définie dans le Makefile :

```bash
make migration-generate
```

Un fichier est créé dans `migrations/` (ex. `VersionXXXXXXXXXXXXXX.php`).

### 3. Appliquer la migration

```bash
make migrate
```

Répondre `yes` si demandé. Les tables sont créées ou mises à jour en base.

### 4. Vérifier la base de données

**Cohérence schéma / entités :**

```bash
make console CMD="doctrine:schema:validate"
```

**Lister les tables (PostgreSQL) :**

```bash
make console CMD="dbal:run-sql \"SELECT tablename FROM pg_tables WHERE schemaname = 'public'\""
```

Tu dois voir au minimum les tables `user`, `event`, `reservation` et `doctrine_migration_versions`.
