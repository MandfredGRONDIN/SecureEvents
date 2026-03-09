# Image de base PHP 8.3 pour Symfony (alignée sur composer.lock et dépendances dev)
FROM php:8.3-cli-bookworm

# Arguments pour personnaliser l'utilisateur (permissions sur les fichiers montés)
# On utilise "appuser" car "www-data" existe déjà dans l'image PHP
ARG user=appuser
ARG uid=1000

# Dépendances système nécessaires aux extensions PHP
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    libicu-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP requises par Symfony 7
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    intl \
    opcache \
    zip \
    bcmath \
    exif \
    pcntl

# iconv et ctype sont intégrés à PHP 8.2, pas besoin de les activer

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Créer un utilisateur non-root (uid = UID hôte) pour les permissions sur les volumes montés
RUN useradd -G www-data,root -u $uid -d /home/$user -m $user
RUN mkdir -p /home/$user/.composer && chown -R $user:$user /home/$user

# Script d'entrée : installe Composer au premier lancement si besoin
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]

# Répertoire de travail (le code sera monté ici via docker-compose)
WORKDIR /var/www/html

# Port du serveur PHP intégré
EXPOSE 8000

# Par défaut : serveur de développement Symfony (peut être surchargé dans docker-compose)
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
