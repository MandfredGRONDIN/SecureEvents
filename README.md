# SecureEvents

Projet de classe — application Symfony dockerisée.

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

---

## Gestion des entités et migrations

### 1. Créer les entités

Les entités sont créées avec le composant Maker. Depuis le répertoire du projet (conteneurs démarrés avec `make up`) :

```bash
# Entité User (email, password, roles, firstName, lastName, createdAt)
make console CMD="make:entity User"

# Entité Event (title, description, startDate, location, maxCapacity, isPublished)
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
