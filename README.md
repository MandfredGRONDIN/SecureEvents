# SecureEvents

Projet de classe — application Symfony dockerisée.

---

## Prérequis

- Docker et Docker Compose
- Pour les commandes `make`, exécuter depuis le répertoire **SecureEvents/** (là où se trouvent le `Makefile`, le `Dockerfile` et `compose.yaml`).

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
make migrate
```

L’application est accessible sur **http://localhost:8000**.
