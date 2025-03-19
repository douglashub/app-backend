#!/bin/bash
MAX_ATTEMPTS=10
ATTEMPT=0
DB_HOST="db-postgres-api-micasan-do-user-20111967-0.f.db.ondigitalocean.com"
DB_PORT="25060"

while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    echo "Checking PostgreSQL connection (Attempt $((ATTEMPT+1))/$MAX_ATTEMPTS)"
    nc -zv $DB_HOST $DB_PORT && break
    sleep 5
    ATTEMPT=$((ATTEMPT+1))
done

echo "Running migrations..."
php artisan migrate --force

