# Payment Service

## What this service does
Handles payment processing via Stripe.
Creates Stripe Checkout Sessions and handles incoming Stripe webhooks.
Never stores raw card data — Stripe handles all PCI scope.

## Framework & stack
- Symfony 7
- MySQL (payment_db) — payment records and webhook event log
- Stripe PHP SDK
- Symfony Messenger — RabbitMQ consumer + webhook processing

## Run commands
```bash
docker compose exec payment-service php bin/console doctrine:migrations:migrate
docker compose exec payment-service php bin/phpunit
docker compose exec payment-service php bin/console messenger:consume async --limit=10
# Local Stripe webhook forwarding (dev only):
stripe listen --forward-to localhost:8005/webhooks/stripe
```

## Endpoints
```
POST /payments/checkout-session     Create Stripe Checkout Session for an order
GET  /payments/{orderId}            Get payment status for an order
POST /webhooks/stripe               Stripe webhook receiver (public, no auth header)
```

## Events consumed
- `order.confirmed` → create Stripe Checkout Session (or trigger auto-charge if saved card)

## Events published
- `payment.completed` → payload: { orderId, userId, amount, currency, stripePaymentId, paidAt }
- `payment.failed`    → payload: { orderId, userId, reason, failedAt }

## Stripe webhook events handled
- `payment_intent.succeeded`   → publish payment.completed
- `payment_intent.failed`      → publish payment.failed
- `checkout.session.completed` → publish payment.completed
- All others: log and return 200 (Stripe retries on non-2xx)

## Database tables
- `payments` — id (UUID), order_id, stripe_payment_intent_id, amount, currency, status, created_at
- `webhook_events` — id, stripe_event_id (unique), type, payload, processed_at, error

## Security rules
- ALWAYS verify Stripe webhook signature before processing: `\Stripe\Webhook::constructEvent()`
- Stripe secret key in env only — never hardcoded, never logged
- Webhook endpoint must be excluded from CSRF protection
- Idempotency: check webhook_events.stripe_event_id before processing — Stripe sends duplicates
- Never log the full webhook payload — it may contain card metadata

## Stripe config (env vars)
```
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_CURRENCY=eur
```

## Testing
```bash
docker compose exec payment-service php bin/phpunit --testdox
```
Use Stripe test mode keys in all environments except production.
For webhook tests, use the Stripe CLI or construct mock webhook payloads with the test secret.
Never make real Stripe API calls in unit tests — mock the Stripe client.
