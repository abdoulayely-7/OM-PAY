php artisan l5-swagger:generate
#!/bin/bash

# Créer le fichier .env si nécessaire
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cat > .env << EOF
APP_NAME="OM-Pay"
APP_ENV=production
APP_KEY=${APP_KEY:-}
APP_DEBUG=false
DB_CONNECTION=${DB_CONNECTION:-mongo}
MONGO_DB_URL=${MONGO_DB_URL:-mongodb+srv://lydevtech:Mouhamm%40dsws632@cluster0.xeygdpt.mongodb.net/}
MONGO_DB_DATABASE=${MONGO_DB_DATABASE:-om_pay_db}
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
LOG_LEVEL=info
EOF
fi

# Afficher les variables de connexion DB pour debug
echo "Database configuration:"
echo "DB_CONNECTION: $DB_CONNECTION"
echo "MONGO_DB_URL: $MONGO_DB_URL"
echo "MONGO_DB_DATABASE: $MONGO_DB_DATABASE"

# Générer la clé d'application si nécessaire
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Tester la connexion MongoDB
echo "Testing MongoDB connection..."
if php -r "
try {
    \$manager = new MongoDB\Driver\Manager(\$_ENV['MONGO_DB_URL']);
    \$command = new MongoDB\Driver\Command(['ping' => 1]);
    \$manager->executeCommand(\$_ENV['MONGO_DB_DATABASE'], \$command);
    echo 'MongoDB connection successful!';
} catch (Exception \$e) {
    echo 'MongoDB connection failed: ' . \$e->getMessage();
    exit(1);
}
"; then
    echo "MongoDB connection test passed!"
else
    echo "MongoDB connection test failed, but continuing..."
fi

# Sanctum est déjà configuré, pas besoin d'installation supplémentaire
echo "Sanctum is configured, no additional setup needed."

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
