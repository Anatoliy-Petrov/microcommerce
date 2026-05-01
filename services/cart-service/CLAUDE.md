# Cart Service

## What this service does
Manages shopping carts. Entirely Redis-backed — no SQL database.
Carts expire automatically via Redis TTL (7 days, refreshed on every write).
Publishes a checkout event; does not handle payment or orders itself.

## Framework & stack
- Laravel 11
- Redis only — no MySQL in this service
- No Horizon needed (no queues — all operations are synchronous Redis calls)

## Run commands
```bash
docker compose exec cart-service php artisan test
docker compose exec cart-service php artisan tinker
# No migrations — Redis only
```

## Endpoints
```
GET    /carts/{userId}                    Get cart with all items and total
POST   /carts/{userId}/items             Add item (or increment qty if already exists)
PUT    /carts/{userId}/items/{productId} Update quantity of a specific item
DELETE /carts/{userId}/items/{productId} Remove specific item
DELETE /carts/{userId}                   Clear entire cart
POST   /carts/{userId}/checkout          Initiate checkout
```

## Redis key structure
```
cart:{userId}        Redis Hash — field=productId, value=JSON item object
```
Each item JSON: `{ product_id, name, price, quantity, added_at }`
TTL: 604800 seconds (7 days). Reset on every write operation.

## Events published
- `cart.checkout_requested` → payload: { userId, items: [...], total, requestedAt }

## Events consumed
- None. Cart service does not listen to any events.

## Important rules
- Price is captured at add-to-cart time from the catalog service (HTTP call).
  If catalog price changes later, the cart price stays as-is. This is intentional.
- Use Redis HSET for individual item updates — never GET-decode-modify-SET (race condition).
- Cart is cleared immediately on checkout event publish, before any confirmation.
  Order service is responsible for failure handling if downstream fails.
- userId comes from X-User-Id header. Never parse JWT here.
- No auth check on cart reads — the gateway already verified the token.

## Redis connection
Use database index 1 (index 0 is reserved for Laravel cache).
Config in config/database.php under redis.cart connection.

## Testing
```bash
docker compose exec cart-service php artisan test
```
Use a real Redis container (redis:7-alpine) for all tests — no mocking Redis.
Call `Redis::connection('cart')->flushdb()` in setUp() for a clean state per test.
