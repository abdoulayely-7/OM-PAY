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
max_attempts=60
attempt=1
while [ $attempt -le $max_attempts ]; do
    if php artisan migrate:status >/dev/null 2>&1; then
        echo "Database is ready!"
        break
    fi
    echo "Database is unavailable - attempt $attempt/$max_attempts - sleeping"
    sleep 3
    attempt=$((attempt + 1))
done

if [ $attempt -gt $max_attempts ]; then
    echo "Database connection failed after $max_attempts attempts"
    echo "Starting application anyway (database might be ready later)..."
fi

# Exécuter les migrations (si la DB est prête)
if php artisan migrate:status >/dev/null 2>&1; then
    echo "Running migrations..."
    php artisan migrate --force
else
    echo "Database not ready, skipping migrations for now..."
fi

# Installer Passport si nécessaire
if php artisan migrate:status >/dev/null 2>&1; then
    echo "Installing Passport keys..."
    php artisan passport:install --force
else
    echo "Database not ready, skipping Passport installation for now..."
fi

# Générer les caches pour la production
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Démarrer Apache
echo "Starting Apache..."
exec apache2-foreground
