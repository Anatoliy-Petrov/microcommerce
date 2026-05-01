# Catalog Service

## What this service does
Manages the product catalog: products, categories, images, and stock levels.
The source of truth for product data and pricing.
Exposes search via Elasticsearch (Laravel Scout).

## Framework & stack
- Laravel 11
- MySQL (catalog_db) — products, categories, stock
- Elasticsearch 8 + Laravel Scout — full-text search
- Laravel Horizon — queue monitoring

## Run commands
```bash
docker compose exec catalog-service php artisan migrate
docker compose exec catalog-service php artisan test
docker compose exec catalog-service php artisan scout:import "App\Models\Product"
docker compose exec catalog-service php artisan horizon
```

## Endpoints
```
GET    /products                List products (paginated, filterable)
GET    /products/{id}           Get single product
POST   /products                Create product (admin only)
PUT    /products/{id}           Update product (admin only)
DELETE /products/{id}           Soft-delete product (admin only)
GET    /products/search?q=      Full-text search via Elasticsearch
GET    /categories              List all categories
GET    /categories/{id}/products Products in a category
POST   /products/{id}/images    Upload product image (admin only)
GET    /products/{id}/stock     Get current stock level
```

## Events published
- `catalog.stock_updated` → payload: { productId, previousQty, newQty, reason }
- `catalog.product_created` → payload: { productId, name, price, categoryId }

## Events consumed
- `order.cancelled` → restock items (increase stock qty)

## Database tables
- `products` — id (UUID), name, description, price (decimal 10,2), category_id, sku, is_active, deleted_at
- `categories` — id, name, slug, parent_id
- `product_images` — id, product_id, url, sort_order, is_primary
- `stock` — product_id (PK), quantity, reserved_quantity, updated_at

## Stock rules
- `quantity` = physical stock
- `reserved_quantity` = held by pending orders (not yet confirmed)
- Available to purchase = quantity - reserved_quantity
- Stock updates must be atomic — use DB transactions
- Never allow available stock to go below 0

## Search
Laravel Scout with Elasticsearch driver.
Searchable fields: name, description, sku, category name.
Re-index on product create/update via Scout observer (automatic).
Manual re-index: `php artisan scout:import "App\Models\Product"`

## Pricing rule
Price stored in catalog is the source of truth.
When the cart service stores a price snapshot, it calls GET /products/{id} at add-to-cart time.
Never call catalog from order-service at checkout — use the price already in the cart payload.

## Testing
```bash
docker compose exec catalog-service php artisan test --parallel
```
Mock Elasticsearch in unit tests. Use a real Elasticsearch container for integration tests.
