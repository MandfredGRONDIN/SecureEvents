# SecureEvents

Projet de classe — application Symfony dockerisée. Gestion d'événements avec visibilité et droits selon le rôle (anonyme, utilisateur connecté, administrateur).

---

## Prérequis

- **Git** (pour cloner le dépôt)
- **Docker** et **Docker Compose**
- Toutes les commandes ci-dessous sont à exécuter depuis le répertoire **SecureEvents/** (celui qui contient le `Makefile` et le `docker-compose`).

---

## Build et installation du projet

À faire une fois après avoir récupéré le code (clone ou téléchargement).

### 1. Récupérer le projet

```bash
git clone <url-du-repo> SecureEvents
cd SecureEvents
```

*(Si le projet est déjà cloné, aller directement dans le répertoire `SecureEvents`.)*

### 2. Construire l'image Docker

Construire l'image de l'application (PHP, extensions, configuration) :

```bash
make build
```

Pour forcer une reconstruction complète sans cache (après modification du Dockerfile par exemple) :

```bash
make rebuild
```

### 3. Démarrer les conteneurs

Lancer les services (app, base de données, mailer) en arrière-plan :

```bash
make up
```

### 4. Installer les dépendances PHP

Exécuter Composer dans le conteneur pour installer les dépendances du projet :

```bash
make install
```

*(En cas d'ajout ou de modification de dépendances dans `composer.json`, utiliser `make update`.)*

### 5. Créer la base de données (migrations)

Appliquer les migrations Doctrine pour créer ou mettre à jour les tables :

```bash
make migrate
```

À ce stade, l'application est installée. Elle est accessible sur **http://localhost:8000** (aucun compte ni donnée de test tant que vous n'avez pas lancé `db-reset` et/ou `seed-demo`).

---

## Lancer l'application

Une fois le **build et l'installation** effectués (section ci-dessus), vous pouvez au quotidien :

### 1. Démarrer l'app (conteneurs déjà construits)

```bash
cd SecureEvents
make up
```

Si les conteneurs tournent déjà, l'application est disponible sur **http://localhost:8000**.

---

### 2. Réinitialiser la base de données

Pour repartir d'une base vide (suppression des données, recréation des tables) et créer un compte administrateur :

```bash
make db-reset
```

Un utilisateur admin est créé avec les valeurs par défaut :
- **Email :** `admin@admin.fr`
- **Mot de passe :** `admin`

Pour personnaliser le compte admin :

```bash
make db-reset EMAIL=admin@example.com PASSWORD=MonMotDePasse FIRST=Prénom LAST=Nom
```

---

### 3. Ajouter des données fictives

**Option A — Jeu de démo complet (recommandé pour tester)**  
Utilisateurs (1 admin + 5 participants) et événements avec réservations cohérentes :

```bash
make seed-demo
```

Cela crée notamment un admin de test : **admin@demo.local** / mot de passe **admin**. Options possibles :

```bash
make seed-demo EVENTS=30    # Nombre d'événements à créer (défaut : 25)
make seed-demo force=1     # Réinitialiser les données démo si la commande a déjà été lancée
```

**Option B — Uniquement des événements**  
Si la base a déjà des utilisateurs et que vous voulez seulement des événements :

```bash
make seed-events
# ou avec un nombre précis :
make seed-events COUNT=40
```

---

### Récap : chaîne complète (depuis un clone vide)

Pour une machine qui n'a jamais lancé le projet (build + installation + BDD + données démo) :

```bash
cd SecureEvents
make build
make up
make install
make migrate
make db-reset
make seed-demo
```

Puis ouvrir **http://localhost:8000** et se connecter avec **admin@demo.local** / **admin** (ou **admin@admin.fr** / **admin** si vous avez fait uniquement `make db-reset` sans `seed-demo`).

---

## Arrêt de l'application

```bash
make down
```

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

## Démarrage minimal (sans reset ni démo)

```bash
cd SecureEvents
make up
make install
```

*Arrêt : `make down` (voir section « Arrêt de l'application » ci-dessus).*

L’application est accessible sur **http://localhost:8000**.

Voir la section **« Lancer l'application »** en tête de README pour le détail (reset BDD, données fictives).

---

## Sécurité et visibilité des événements

- **Non connecté** : voit uniquement les événements **publiés** ; ne peut pas créer ni éditer d'événements (boutons masqués, accès aux URLs redirigé vers le login).
- **Utilisateur connecté (non admin)** : voit les événements **publiés** et les événements **qu'il a créés** (publiés ou non) ; peut créer des événements ; peut éditer/supprimer **uniquement** ceux dont il est le créateur.
- **Administrateur** : voit **tous** les événements ; peut créer, éditer et supprimer n'importe quel événement.

Chaque événement affiche son **créateur** (prénom, nom, email) sur la liste et sur la page détail. La création et l'édition sont protégées par `security.yaml` (ROLE_USER) et par le `EventVoter` (droits EVENT_VIEW, EVENT_EDIT, EVENT_DELETE).

---

## API REST

L’application expose une API REST pour permettre à des partenaires ou applications mobiles de consulter le catalogue d’événements.

### Endpoint : liste des événements

| Méthode | URL | Description |
|--------|-----|-------------|
| `GET` | `/api/events` | Liste des événements **futurs** et **publiés** au format JSON |

- **Accès** : public (aucune authentification requise).
- **Format de réponse** : JSON (composant Serializer Symfony).

Chaque élément du tableau contient notamment : `id`, `title`, `description`, `startDate`, `location`, `maxCapacity`, `isPublished`, `placesDisponibles` (places restantes).

**Exemple d’appel :**

```bash
curl -s http://localhost:8000/api/events
```

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

---

## PostMortem

Le fichier [POSTMORTEM.md](POSTMORTEM.md) décrit le retour d’expérience du projet (difficultés, réussites, améliorations possibles, pistes non réalisées).
