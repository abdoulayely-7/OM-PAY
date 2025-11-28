php artisan l5-swagger:generate
#!/bin/bash

# Créer le fichier .env si nécessaire
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cat > .env << EOF
APP_NAME="OM-Pay"
APP_ENV=production
APP_KEY=${APP_KEY:-}
APP_DEBUG=true
DB_CONNECTION=pgsql
DB_HOST=${DB_HOST:-dpg-d48f51ngi27c73cjrhmg-a.oregon-postgres.render.com}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-om_pay}
DB_USERNAME=${DB_USERNAME:-om_pay_user}
DB_PASSWORD=${DB_PASSWORD:-r5SKL0PoFIoX0kPwmrdwQnIVAbOc1sXo}
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
LOG_LEVEL=info
EOF
fi

# Afficher les variables de connexion DB pour debug
echo "Database configuration:"
echo "DB_HOST: $DB_HOST"
echo "DB_PORT: $DB_PORT"
echo "DB_DATABASE: $DB_DATABASE"
echo "DB_USERNAME: $DB_USERNAME"
echo "DB_PASSWORD: [HIDDEN]"

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
    if php artisan migrate:status >/dev/null 2>&1; then
        echo "Database is ready!"
        break
    fi
    echo "Database is unavailable - attempt $attempt/$max_attempts - sleeping"
    sleep 5
    attempt=$((attempt + 1))
done

if [ $attempt -gt $max_attempts ]; then
    echo "Database connection failed after $max_attempts attempts"
    echo "Starting application anyway (database might be ready later)..."
fi

# Tester la connexion DB de base
echo "Testing basic database connection..."
if php -r "
try {
    \$pdo = new PDO('pgsql:host='.\$_ENV['DB_HOST'].';port='.\$_ENV['DB_PORT'].';dbname='.\$_ENV['DB_DATABASE'], \$_ENV['DB_USERNAME'], \$_ENV['DB_PASSWORD']);
    echo 'Database connection successful!';
} catch (Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage();
    exit(1);
}
"; then
    echo "Basic DB connection test passed!"
else
    echo "Basic DB connection test failed, but continuing..."
fi

# Forcer l'exécution des migrations même si la DB n'est pas détectée
echo "Attempting to run migrations..."
if php artisan migrate --force; then
    echo "Migrations completed successfully!"
else
    echo "Migrations failed, but continuing..."
fi

# Forcer l'installation de Passport
echo "Installing Passport keys..."
if php artisan passport:install --force; then
    echo "Passport keys installed successfully!"
else
    echo "Passport installation failed, but continuing..."
fi

# Générer les clés OAuth si elles n'existent pas
if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
    echo "Generating OAuth keys..."
    php artisan passport:keys --force
fi

# Générer les caches pour la production
echo "Optimizing application..."
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:cache

# Démarrer Apache
echo "Starting Apache..."
exec apache2-foreground
