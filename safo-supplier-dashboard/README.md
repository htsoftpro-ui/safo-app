# Safo Supplier Dashboard

Vue.js 3 supplier dashboard for managing products, orders, and store operations.

## Stack

- Vue 3 + TypeScript
- Vite (build tool)
- Pinia (state management)
- Vue Router (routing)
- Axios (API client)
- Tailwind CSS (styling)

## Quick Start

```bash
# Install dependencies
npm install

# Development (with API proxy to localhost:8000)
npm run dev

# Build for production
npm run build
```

## Development

The dev server runs on `http://localhost:3000` and proxies API requests to `http://localhost:8000`.

Make sure the Laravel backend is running before starting the dashboard.

## Pages

| Page | Route | Description |
|------|-------|-------------|
| Login | `/login` | Supplier login |
| Dashboard | `/` | Stats, recent orders, top products |
| Products | `/products` | Product list with search/filter |
| Product Create | `/products/create` | Add new product |
| Product Edit | `/products/:id/edit` | Edit product |
| Orders | `/orders` | Order list with status filter |
| Order Detail | `/orders/:id` | Order details + status actions |
| Profile | `/profile` | Store info + password change |

## API Integration

All API calls go through `src/api/index.ts`. The API client:
- Attaches Bearer token from localStorage
- Redirects to login on 401
- Proxies through Vite dev server in development

## Build Output

Production build outputs to `dist/` directory. Serve with Nginx or any static file server.

## Environment

No `.env` file needed. API base URL is configured in `vite.config.ts` proxy (development) and should be configured for production deployment.
