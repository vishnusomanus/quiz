#!/bin/sh
set -e

echo "📂 Current directory:"
pwd

echo "⏳ Waiting for MySQL to be ready..."
while ! nc -z mysql 3306; do
  sleep 0.1
done
echo "✅ MySQL is up."

# Go to Laravel app directory
cd /var/www/html || exit 1

echo "📂 Current directory: $(pwd)"
echo "📁 Listing contents:"
ls -la

# Install Composer dependencies (consider using `install` in production)
echo "📦 Installing composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Verify vendor folder
echo "📁 Verifying vendor directory..."
ls -la vendor/ || echo "⚠️ Vendor directory missing!"

# Set correct permissions without loosening Passport key modes
echo "🔐 Setting permissions..."
chown -R www-data:www-data /var/www/html
chmod -R ug+rwx storage bootstrap/cache

# Set Laravel env
export APP_ENV=production

# Run migrations (with --force and --no-interaction)
echo "🗃️ Running migrations..."
php artisan migrate --force --env=production --no-interaction

# Seed the DB (enable with SEED_DATABASE=true, default: true in Docker)
if [ "$SEED_DATABASE" != "false" ]; then
    echo "🌱 Running database seeders..."
    php artisan db:seed --force --env=production --no-interaction
    echo "✅ Seeding complete."
else
    echo "⏭️ Skipping seeding (SEED_DATABASE=false)."
fi

echo "🔑 Preparing Laravel Passport..."
if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
    php artisan passport:keys --no-interaction
fi

# Public disk for uploaded option images
php artisan storage:link --force --no-interaction

# Passport 13 hashes client secrets; skip already-hashed values
php artisan passport:hash --force --no-interaction

if php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    app(Laravel\Passport\ClientRepository::class)->personalAccessClient("users");
    exit(0);
} catch (RuntimeException $e) {
    exit(1);
}
'; then
    echo "✅ Personal access client already exists."
else
    echo "🔑 Generating Laravel Passport personal access client..."
    php artisan passport:client --personal --name="Personal Access Client" --provider=users --no-interaction
fi

# Passport 13 validates key file permissions
if [ -f storage/oauth-private.key ] && [ -f storage/oauth-public.key ]; then
    chown www-data:www-data storage/oauth-private.key storage/oauth-public.key
    chmod 600 storage/oauth-private.key
    chmod 660 storage/oauth-public.key
    echo "✅ Passport keys ready."
fi

echo "🚀 Starting PHP-FPM..."
exec "$@"
