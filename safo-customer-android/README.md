# Safo — B2B Wholesale Marketplace

Android customer app for ordering wholesale goods.

## Stack

- Kotlin 2.1
- Jetpack Compose (Material 3)
- MVVM + Hilt DI
- Retrofit + OkHttp
- Coroutines
- Navigation Compose
- DataStore (secure token storage)
- Coil (image loading)

## Setup

1. Start the Laravel backend:
   ```bash
   cd safo-backend
   php artisan serve
   ```

2. Update `BASE_URL` in `NetworkModule.kt`:
   - Emulator: `http://10.0.2.2:8000/api/v1/`
   - Device: `http://YOUR_IP:8000/api/v1/`

3. Build and run:
   ```bash
   ./gradlew installDebug
   ```

## Pages

| Page | Description |
|------|-------------|
| Login | Phone + password |
| Register | Name + phone + password |
| Home | Categories, featured, new arrivals |
| Products | Grid list, search, filters |
| Product Detail | Images, price, stock, add to cart |
| Cart | Items, quantity, checkout |
| Orders | List with status filters |
| Order Detail | Items, timeline, cancel/confirm |
| Profile | Info, update, change password |
| Addresses | CRUD addresses |

## API Integration

All data comes from the Laravel API. No mock data.

## Arabic RTL

Full Arabic support with RTL layout.
