# Analytics Service

## What this service does
Consumes all domain events and stores them for reporting.
Provides aggregated metrics for an internal admin dashboard.
Read-heavy. Optimised for queries, not writes.

## Framework & stack
- Symfony 7
- MySQL (analytics_db) — event log and pre-aggregated metrics tables
- Symfony Messenger — RabbitMQ consumer
- Optional: ClickHouse for high-volume event storage (start with MySQL, migrate later)

## Run commands
```bash
docker compose exec analytics-service php bin/console doctrine:migrations:migrate
docker compose exec analytics-service php bin/phpunit
docker compose exec analytics-service php bin/console messenger:consume analytics --limit=50
```

## Endpoints
```
GET /analytics/revenue?from=&to=          Total revenue for period
GET /analytics/orders?from=&to=           Order counts by status
GET /analytics/products/top?limit=10      Top products by revenue
GET /analytics/users/new?from=&to=        New user registrations
GET /analytics/events?type=&from=&to=     Raw event log (paginated)
```
All endpoints are internal — not exposed via the public API gateway.
Protected by a shared internal API key (X-Internal-Key header).

## Events consumed (all of them)
- `user.registered`
- `catalog.stock_updated`
- `catalog.product_created`
- `cart.checkout_requested`
- `order.confirmed`
- `order.cancelled`
- `order.shipped`
- `payment.completed`
- `payment.failed`

## Database tables
- `events` — id, event_type, payload (JSON), occurred_at, ingested_at
- `daily_revenue` — date (PK), total_amount, order_count, updated_at
- `product_sales` — product_id, product_name, units_sold, revenue, period_date
- `user_signups` — date (PK), count, updated_at

## Important rules
- The `events` table is append-only. Never update or delete rows.
- Pre-aggregate into summary tables (daily_revenue etc.) on ingestion — do not compute on query.
- If an event cannot be parsed, log the raw payload and skip — never crash the consumer.
- Payload column stores the full raw event JSON for debugging and re-processing.
- This service has no write API. All data enters via RabbitMQ consumer only.
- Add indexes on occurred_at and event_type — all queries filter by these.

## Aggregation strategy
On each `payment.completed` event:
1. Insert into events table
2. Upsert into daily_revenue for today's date (INSERT ... ON DUPLICATE KEY UPDATE)
3. Upsert into product_sales for each item in the payload

Use DB transactions for steps 2+3 so partial aggregation never happens.

## Testing
```bash
docker compose exec analytics-service php bin/phpunit --testdox
```
Test the message handlers in isolation.
Assert both raw event insertion and aggregation table updates.
Use in-memory SQLite for unit tests, MySQL for integration tests.
