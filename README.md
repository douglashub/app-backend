# Sistema de Gestão de Transporte Escolar

## Development Setup

```bash
docker-compose up -d --build
docker exec -it app-backend bash
```

## Database Initialization

```bash
php artisan migrate:fresh --seed
```

## Starting the Application

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## API Access

- **Base URL**: http://{app-host}:8000/api
- **Documentation**: http://{app-host}:8000/api/documentation

### Key Services:
- MySQL @ localhost:3306
- Redis @ localhost:6379

Note: Ensure `.env` file is properly configured with database credentials before migration.
