# Order Service

## What this service does
Creates and manages orders through their full lifecycle.
Uses the Symfony Workflow component as a state machine for order states.
The authoritative record of what was ordered, at what price, and in what state.

## Framework & stack
- Symfony 7
- MySQL (order_db)
- Symfony Workflow component — state machine
- Symfony Messenger — RabbitMQ consumer

## Run commands
```bash
docker compose exec order-service php bin/console doctrine:migrations:migrate
docker compose exec order-service php bin/phpunit
docker compose exec order-service php bin/console messenger:consume async --limit=10
docker compose exec order-service php bin/console workflow:dump order_process | dot -Tpng > order_flow.png
```

## Endpoints
```
GET    /orders/{id}             Get order detail
GET    /orders?userId={id}      List orders for a user (paginated)
POST   /orders/{id}/cancel      Cancel an order (if cancellable)
GET    /orders/{id}/timeline    Get state transition history
```
Note: Orders are created by consuming cart.checkout_requested — not via HTTP POST.

## Order state machine
States: `pending` → `confirmed` → `shipped` → `delivered`
                 ↘ `cancelled` (from pending or confirmed only)

Transitions:
- `confirm`   pending → confirmed       (triggered by payment.completed event)
- `ship`      confirmed → shipped       (triggered by admin/fulfillment action)
- `deliver`   shipped → delivered       (triggered by delivery webhook)
- `cancel`    pending|confirmed → cancelled  (user request or payment.failed)

Defined in config/packages/workflow.yaml. Never hardcode state strings — use the Workflow service.

## Events consumed
- `cart.checkout_requested`  → create order in `pending` state
- `payment.completed`        → transition order to `confirmed`
- `payment.failed`           → transition order to `cancelled`

## Events published
- `order.confirmed`   → payload: { orderId, userId, items, total, confirmedAt }
- `order.cancelled`   → payload: { orderId, userId, items, reason, cancelledAt }
- `order.shipped`     → payload: { orderId, trackingNumber, shippedAt }

## Database tables
- `orders` — id (UUID), user_id, status, subtotal, total, created_at, updated_at
- `order_items` — id, order_id, product_id, product_name, unit_price, quantity, line_total
- `order_transitions` — id, order_id, from_state, to_state, triggered_by, created_at

## Important rules
- Snapshot product name and price from the cart payload into order_items. Never look up catalog again.
- All state transitions must go through the Symfony Workflow service — never update status column directly.
- Log every transition in order_transitions for the audit trail.
- Orders cannot be modified after `confirmed` — only cancelled.
- Idempotency: if `cart.checkout_requested` arrives with a duplicate cart ID, skip silently.

## Testing
```bash
docker compose exec order-service php bin/phpunit --testdox
```
Use in-memory SQLite for unit tests. Integration tests use order_test_db (MySQL).
Test the workflow transitions explicitly — assert allowed/blocked transitions.
