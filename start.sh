#!/bin/bash

# Attendre que la base de données soit prête
echo "Waiting for database..."
while ! pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USERNAME; do
    sleep 1
done
echo "Database is ready!"

# Exécuter les migrations
echo "Running migrations..."
php artisan migrate --force

# Exécuter les seeders
echo "Seeding database..."
php artisan db:seed --force

# Installer Passport
echo "Installing Passport..."
php artisan passport:install --force

# Démarrer Apache
echo "Starting Apache..."
apache2-foreground
