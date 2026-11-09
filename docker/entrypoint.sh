#!/bin/sh
# Prépare l'application au démarrage du conteneur, puis lance Apache.
set -e

if [ ! -f vendor/autoload.php ]; then
    echo "→ Installation des dépendances (composer install)…"
    composer install --no-interaction --prefer-dist --no-progress
fi

echo "→ Attente de MySQL…"
until php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; do
    sleep 2
done

echo "→ Migrations de la base"
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "→ Données de démonstration (si la base est vide)"
php bin/console app:demo:load --if-empty

mkdir -p var && chown -R www-data:www-data var

exec "$@"
