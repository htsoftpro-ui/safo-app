# Safo — Backend API

## Quick Start

```bash
# 1. Install dependencies
composer install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database
php artisan migrate
php artisan db:seed

# 4. Storage link
php artisan storage:link

# 5. Start
php artisan serve
```

## Architecture

```
app/
├── Http/Controllers/API/       # Customer-facing API
├── Http/Controllers/Supplier/  # Supplier dashboard API
├── Http/Controllers/Admin/     # Admin dashboard API
├── Http/Middleware/            # Auth, Role checks
├── Http/Requests/             # Form validation
├── Http/Resources/            # API response formatting
├── Models/                    # Eloquent models
├── Services/                  # Business logic
├── Policies/                  # Authorization
└── Exceptions/                # Error handling
```

## API Versioning

All API routes are prefixed with `/api/v1/`.

## Roles

- **customer** — browses, orders, tracks
- **supplier** — manages products, fulfills orders
- **admin** — full system control
