# Makefile pour le projet Symfony SecureEvents (Docker)
# Usage : make [cible] ou make help

.PHONY: help up down build rebuild install migrate schema-update cache-clear console logs shell test

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
	@echo "  make migration-generate - Générer une migration (diff entités / base)"
	@echo "  make migrate     - Exécuter les migrations Doctrine"
	@echo "  make schema-update - Mettre à jour le schéma DB depuis les entités"
	@echo "  make cache-clear - Vider le cache Symfony"
	@echo "  make console     - Lancer une commande Symfony (ex: make console CMD='list')"
	@echo "  make logs        - Afficher les logs du service app"
	@echo "  make shell       - Ouvrir un shell dans le conteneur app"
	@echo "  make test        - Lancer les tests PHPUnit"
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
