#!/bin/bash

# Créer le fichier .env si nécessaire
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cat > .env << EOF
APP_NAME="OM-Pay"
APP_ENV=production
APP_KEY=${APP_KEY:-}
APP_DEBUG=false
DB_CONNECTION=pgsql
DB_HOST=${DB_HOST:-localhost}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-om_pay}
DB_USERNAME=${DB_USERNAME:-}
DB_PASSWORD=${DB_PASSWORD:-}
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
LOG_LEVEL=info
EOF
fi

# Générer la clé d'application si nécessaire
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Attendre que la base de données soit prête
echo "Waiting for database to be ready..."
max_attempts=30
attempt=1
while [ $attempt -le $max_attempts ]; do
    if pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" 2>/dev/null; then
        echo "Database is ready!"
        break
    fi
    echo "Database is unavailable - attempt $attempt/$max_attempts - sleeping"
    sleep 2
    attempt=$((attempt + 1))
done

if [ $attempt -gt $max_attempts ]; then
    echo "Database connection failed after $max_attempts attempts"
    exit 1
fi

# Exécuter les migrations
echo "Running migrations..."
php artisan migrate --force

# Installer Passport si nécessaire
echo "Installing Passport keys..."
php artisan passport:install --force

# Générer les caches pour la production
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Démarrer Apache
echo "Starting Apache..."
exec apache2-foreground
