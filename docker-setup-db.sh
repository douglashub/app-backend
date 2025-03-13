#!/bin/bash

# Database setup script for Docker environment
echo "Starting database setup for School Transport System"

# Step 1: Run migrations
echo "Running migrations..."
docker-compose exec -T app php artisan migrate:fresh

# Check if migrations were successful
if [ $? -eq 0 ]; then
    echo "✓ Migrations completed successfully."
else
    echo "✗ Migrations failed. Check your migration files."
    exit 1
fi

# Step 2: Run schema fix migration if needed
echo "Running schema fix migration..."
docker-compose exec -T app php artisan migrate --path=database/migrations/2024_03_13_000001_fix_schema_issues.php

# Step 3: Run seeders
echo "Seeding database with test data..."
docker-compose exec -T app php artisan db:seed

# Check if seeding was successful
if [ $? -eq 0 ]; then
    echo "✓ Database seeded successfully."
else
    echo "✗ Database seeding failed. Check your seeder files."
    exit 1
fi

echo "✓ Database setup completed! The School Transport System is ready for use."
echo "You can now access the API at http://localhost/api/test"
