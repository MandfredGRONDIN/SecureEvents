#!/bin/sh
# Point d'entrée du conteneur : installe les dépendances Composer si nécessaire, puis démarre l'app

set -e

# Installer les dépendances si vendor/ n'existe pas (premier lancement ou volume vide)
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "Installation des dépendances Composer..."
    composer install --no-interaction --prefer-dist
fi

# Réchauffer le cache Symfony au démarrage pour éviter un premier chargement très lent (timeout navigateur)
if [ -f /var/www/html/bin/console ]; then
    echo "Réchauffement du cache Symfony..."
    php /var/www/html/bin/console cache:warmup --env=dev 2>/dev/null || true
fi

# Exécuter la commande passée (par défaut : serveur PHP intégré)
exec "$@"
