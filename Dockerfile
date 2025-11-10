# Utiliser l'image PHP officielle avec Apache
FROM php:8.3-apache

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

# Copier le reste du code
COPY . .

# Copier les fichiers de configuration
COPY composer.json composer.lock ./

# Installer les dépendances PHP
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Définir les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Créer le fichier .env avec les bonnes valeurs
RUN echo "APP_NAME=\"OM-Pay\"" > .env && \
    echo "APP_ENV=production" >> .env && \
    echo "APP_KEY=" >> .env && \
    echo "APP_DEBUG=false" >> .env && \
    echo "DB_CONNECTION=pgsql" >> .env && \
    echo "DB_HOST=dpg-d48f51ngi27c73cjrhmg-a.oregon-postgres.render.com" >> .env && \
    echo "DB_PORT=5432" >> .env && \
    echo "DB_DATABASE=om_pay" >> .env && \
    echo "DB_USERNAME=om_pay_user" >> .env && \
    echo "DB_PASSWORD=r5SKL0PoFIoX0kPwmrdwQnIVAbOc1sXo" >> .env && \
    echo "CACHE_DRIVER=redis" >> .env && \
    echo "SESSION_DRIVER=redis" >> .env && \
    echo "QUEUE_CONNECTION=sync" >> .env && \
    echo "REDIS_HOST=127.0.0.1" >> .env && \
    echo "REDIS_PASSWORD=null" >> .env && \
    echo "REDIS_PORT=6379" >> .env && \
    php artisan key:generate

# Configurer Apache pour Laravel
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Exposer le port 80
EXPOSE 80

# Démarrer Apache au premier plan
CMD apache2-foreground
