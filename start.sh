#!/bin/bash

# Attendre que la base de données soit prête
echo "Waiting for database to be ready..."
while ! pg_isready -h $DB_HOST -p $DB_PORT -U "$DB_USERNAME"; do
  echo "Database is unavailable - sleeping"
  sleep 2
done

echo "Database is ready!"

# Exécuter les migrations si nécessaire
php artisan migrate --force

# Générer les clés Passport si nécessaire
php artisan passport:install --force

# Démarrer Apache
echo "Starting Apache..."
apache2-foreground
