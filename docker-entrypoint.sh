#!/bin/sh
# Point d'entrée du conteneur : installe les dépendances Composer si nécessaire, puis démarre l'app

set -e

# Installer les dépendances si vendor/ n'existe pas (premier lancement ou volume vide)
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "Installation des dépendances Composer..."
    composer install --no-interaction --prefer-dist
fi

# Exécuter la commande passée (par défaut : serveur PHP intégré)
exec "$@"
