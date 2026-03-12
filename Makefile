# Makefile pour le projet Symfony SecureEvents (Docker)
# Usage : make [cible] ou make help

.PHONY: help up down build rebuild install update migrate schema-update cache-clear console logs shell test db-reset seed-events seed-demo

# Commande Docker Compose (depuis ce répertoire)
DOCKER_COMPOSE = docker compose
EXEC_APP = $(DOCKER_COMPOSE) exec app

# Cible par défaut : afficher l'aide
help:
	@echo "SecureEvents - Commandes disponibles :"
	@echo ""
	@echo "  make up          - Démarrer les conteneurs (app, database, mailer)"
	@echo "  make down        - Arrêter les conteneurs"
	@echo "  make build       - Construire l'image de l'app"
	@echo "  make rebuild     - Reconstruire sans cache et redémarrer"
	@echo "  make install     - composer install dans le conteneur"
	@echo "  make update      - composer update (mise à jour des dépendances)"
	@echo "  make migration-generate - Générer une migration (diff entités / base)"
	@echo "  make migrate     - Exécuter les migrations Doctrine"
	@echo "  make schema-update - Mettre à jour le schéma DB depuis les entités"
	@echo "  make cache-clear - Vider le cache Symfony"
	@echo "  make console     - Lancer une commande Symfony (ex: make console CMD='list')"
	@echo "  make logs        - Afficher les logs du service app"
	@echo "  make shell       - Ouvrir un shell dans le conteneur app"
	@echo "  make test        - Lancer les tests PHPUnit"
	@echo "  make db-reset    - Réinitialiser la BDD (drop, create, migrate) et créer un utilisateur"
	@echo "  make seed-events - Créer des événements de test (option : COUNT=40)"
	@echo "  make seed-demo   - Créer utilisateurs de démo (rôles différents) + événements cohérents"
	@echo ""

# Démarrer les conteneurs en arrière-plan
up:
	$(DOCKER_COMPOSE) up -d

# Arrêter les conteneurs
down:
	$(DOCKER_COMPOSE) down

# Construire l'image de l'app (sans démarrer)
build:
	$(DOCKER_COMPOSE) build app

# Reconstruire l'image sans cache puis redémarrer
rebuild:
	$(DOCKER_COMPOSE) build --no-cache app
	$(DOCKER_COMPOSE) up -d

# Installer les dépendances Composer
install:
	$(EXEC_APP) composer install

# Mettre à jour les dépendances Composer (après modification du composer.json)
update:
	$(EXEC_APP) composer update

# Générer une migration à partir des entités (diff avec la base)
migration-generate:
	$(EXEC_APP) php bin/console doctrine:migrations:diff

# Exécuter les migrations Doctrine
migrate:
	$(EXEC_APP) php bin/console doctrine:migrations:migrate --no-interaction

# Mettre à jour le schéma de la base (sans migrations)
schema-update:
	$(EXEC_APP) php bin/console doctrine:schema:update --force

# Vider le cache Symfony
cache-clear:
	$(EXEC_APP) php bin/console cache:clear

# Commande Symfony (usage : make console CMD="doctrine:migrations:status")
console:
	$(EXEC_APP) php bin/console $(CMD)

# Afficher les logs du service app
logs:
	$(DOCKER_COMPOSE) logs -f app

# Shell interactif dans le conteneur app
shell:
	$(EXEC_APP) sh

# Lancer les tests PHPUnit
test:
	$(EXEC_APP) php bin/phpunit

# Créer des événements de test pour la visibilité (anonyme / user / admin)
# Options : make seed-events COUNT=50
seed-events:
	$(EXEC_APP) php bin/console app:events:seed --count="$(or $(COUNT),25)"

# Créer utilisateurs de démo (1 admin + 5 users) et événements avec répartition réaliste (certains users sans event)
# Options : make seed-demo EVENTS=30  ou  make seed-demo force=1
seed-demo:
	$(EXEC_APP) php bin/console app:seed:demo --events="$(or $(EVENTS),25)" $(if $(force),--force,)

# Réinitialiser la BDD et créer un utilisateur (admin@secureevents.local / admin par défaut)
# Options : make db-reset EMAIL=... PASSWORD=... FIRST=... LAST=...
db-reset:
	$(EXEC_APP) php bin/console app:db:reset-with-user \
		--email="$(or $(EMAIL),admin@admin.fr)" \
		--password="$(or $(PASSWORD),admin)" \
		--first-name="$(or $(FIRST),Admin)" \
		--last-name="$(or $(LAST),SecureEvents)"
