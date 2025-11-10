#!/bin/bash

# Script de build pour Render
echo "Building Docker image..."
docker build -t om-pay .

echo "Running migrations..."
docker run --rm \
  -e APP_ENV=production \
  -e APP_KEY=$APP_KEY \
  -e DB_CONNECTION=pgsql \
  -e DB_HOST=dpg-d48f51ngi27c73cjrhmg-a.oregon-postgres.render.com \
  -e DB_PORT=5432 \
  -e DB_DATABASE=om_pay \
  -e DB_USERNAME=om_pay_user \
  -e DB_PASSWORD=r5SKL0PoFIoX0kPwmrdwQnIVAbOc1sXo \
  om-pay \
  php artisan migrate --force

echo "Installing Passport keys..."
docker run --rm \
  -e APP_ENV=production \
  -e APP_KEY=$APP_KEY \
  -e DB_CONNECTION=pgsql \
  -e DB_HOST=dpg-d48f51ngi27c73cjrhmg-a.oregon-postgres.render.com \
  -e DB_PORT=5432 \
  -e DB_DATABASE=om_pay \
  -e DB_USERNAME=om_pay_user \
  -e DB_PASSWORD=r5SKL0PoFIoX0kPwmrdwQnIVAbOc1sXo \
  om-pay \
  php artisan passport:install --force

echo "Build completed successfully!"
