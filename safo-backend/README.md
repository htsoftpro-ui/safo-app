# Safo — B2B Wholesale Marketplace API

Backend API for Safo B2B wholesale marketplace, built with Laravel 11.

## Tech Stack

- **Framework:** Laravel 11
- **PHP:** 8.2+
- **Database:** MySQL 8.0
- **Auth:** Laravel Sanctum
- **Cache:** Redis (optional)

## Quick Start

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Configure database in .env, then:
php artisan migrate
php artisan db:seed

# Start development server
php artisan serve
```

## API Endpoints

### Public (No Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/register` | Register |
| POST | `/api/v1/auth/login` | Login |
| GET | `/api/v1/health` | Health check |
| GET | `/api/v1/categories` | List categories |
| GET | `/api/v1/categories/{id}` | Category details |
| GET | `/api/v1/categories/{id}/products` | Category products |
| GET | `/api/v1/products` | List products |
| GET | `/api/v1/products/featured` | Featured products |
| GET | `/api/v1/products/new-arrivals` | New arrivals |
| GET | `/api/v1/products/best-sellers` | Best sellers |
| GET | `/api/v1/products/search?q=` | Search products |
| GET | `/api/v1/products/{id}` | Product details |

### Protected (Auth Required — Customer)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/logout` | Logout |
| GET | `/api/v1/auth/me` | Current user |
| GET | `/api/v1/profile` | Profile |
| PUT | `/api/v1/profile` | Update profile |
| POST | `/api/v1/profile/avatar` | Upload avatar |
| POST | `/api/v1/profile/change-password` | Change password |
| DELETE | `/api/v1/profile` | Delete account |
| GET | `/api/v1/addresses` | List addresses |
| POST | `/api/v1/addresses` | Add address |
| PUT | `/api/v1/addresses/{id}` | Update address |
| DELETE | `/api/v1/addresses/{id}` | Delete address |
| GET | `/api/v1/cart` | View cart |
| POST | `/api/v1/cart` | Add to cart |
| PUT | `/api/v1/cart/{id}` | Update cart item |
| DELETE | `/api/v1/cart/{id}` | Remove from cart |
| DELETE | `/api/v1/cart` | Clear cart |
| GET | `/api/v1/orders` | List orders |
| POST | `/api/v1/orders` | Create order |
| GET | `/api/v1/orders/{id}` | Order details |
| POST | `/api/v1/orders/{id}/cancel` | Cancel order |
| POST | `/api/v1/orders/{id}/confirm-delivery` | Confirm delivery |

### Supplier (Auth + Role: supplier)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/supplier/orders` | List orders |
| GET | `/api/v1/supplier/orders/{id}` | Order details |
| POST | `/api/v1/supplier/orders/{id}/accept` | Accept order |
| POST | `/api/v1/supplier/orders/{id}/reject` | Reject order |
| POST | `/api/v1/supplier/orders/{id}/process` | Start processing |
| POST | `/api/v1/supplier/orders/{id}/ready` | Mark ready |
| POST | `/api/v1/supplier/orders/{id}/ship` | Ship order |
| GET | `/api/v1/supplier/products` | List products |
| POST | `/api/v1/supplier/products` | Add product |
| PUT | `/api/v1/supplier/products/{id}` | Update product |
| DELETE | `/api/v1/supplier/products/{id}` | Delete product |
| PATCH | `/api/v1/supplier/products/{id}/stock` | Update stock |

## Order Status Flow

```
pending → confirmed → processing → ready → shipped → delivered → returned
   ↓          ↓           ↓
cancelled  cancelled   cancelled
```

## Test Data

After seeding:

| Role | Phone | Password |
|------|-------|----------|
| Admin | 770000001 | password123 |
| Supplier 1 | 771000001 | password123 |
| Supplier 2 | 771000002 | password123 |
| Supplier 3 | 771000003 | password123 |
| Customer 1 | 772000001 | password123 |
| Customer 2 | 772000002 | password123 |
| Customer 3 | 772000003 | password123 |
| Customer 4 | 772000004 | password123 |
| Customer 5 | 772000005 | password123 |

## Architecture

- **Service Layer:** Business logic in `App\Services\*`
- **Resources:** All responses via `App\Http\Resources\*`
- **Form Requests:** Validation via `App\Http\Requests\*`
- **Models:** Scopes, accessors, business rules in models
- **Middleware:** Role-based access via `CheckRole`

## Project Structure

```
app/
├── Exceptions/         # Custom exceptions
├── Http/
│   ├── Controllers/API/  # API controllers
│   ├── Middleware/        # Custom middleware
│   ├── Requests/         # Form request validation
│   └── Resources/        # API resource transformers
├── Models/             # Eloquent models
└── Services/           # Business logic services
database/
├── migrations/         # Database migrations
└── seeders/            # Test data seeders
routes/
└── api.php             # API route definitions
```
