#!/bin/bash

# Attendre que la base de données soit prête
echo "Waiting for database to be ready..."
while ! pg_isready -h ${DB_HOST} -p ${DB_PORT} -U ${DB_USERNAME} 2>/dev/null; do
    echo "Database is unavailable - sleeping"
    sleep 2
done

echo "Database is up - executing migrations and seeders"

# Générer la clé d'application si elle n'existe pas
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate
fi

# Exécuter les migrations
echo "Running migrations..."
php artisan migrate --force

# Exécuter les seeders seulement en développement
if [ "$APP_ENV" = "local" ]; then
    echo "Seeding database..."
    php artisan db:seed --force
fi

# Installer Passport si nécessaire
echo "Installing Passport..."
php artisan passport:install --force

# Créer les clés JWT si elles n'existent pas
if [ ! -f storage/oauth-public.key ] || [ ! -f storage/oauth-private.key ]; then
    echo "Generating Passport keys..."
    php artisan passport:keys
fi

# Créer le client personnel d'accès si nécessaire
if ! php artisan passport:client --personal --name="Laravel Personal Access Client" --no-interaction 2>/dev/null; then
    echo "Personal access client already exists or failed to create"
fi

# Optimiser l'application pour la production
if [ "$APP_ENV" = "production" ]; then
    echo "Optimizing application for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Démarrer Apache
echo "Starting Apache..."
apache2-foreground
