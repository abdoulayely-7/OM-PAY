# Utiliser l'image PHP officielle avec Apache
FROM php:8.2-apache

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-client \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurer Apache
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers de l'application
COPY . /var/www/html

# Installer les dépendances PHP
RUN composer install --optimize-autoloader --no-dev

# Générer la clé d'application
RUN php artisan key:generate

# Configurer les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Créer le script de démarrage
RUN echo '#!/bin/bash\n\
echo "Waiting for database..."\n\
while ! pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USERNAME; do\n\
    sleep 1\n\
done\n\
echo "Database is ready!"\n\
\n\
php artisan migrate --force\n\
php artisan db:seed --force\n\
php artisan passport:install --force\n\
\n\
apache2-foreground' > /usr/local/bin/start.sh

RUN chmod +x /usr/local/bin/start.sh

# Exposer le port 80
EXPOSE 80

# Commande de démarrage
CMD ["/usr/local/bin/start.sh"]
