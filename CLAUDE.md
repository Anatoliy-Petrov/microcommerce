# PHP E-Commerce Microservices — Monorepo

## Project overview
Pet project: e-commerce platform built with PHP microservices.
Monorepo with 8 services. Each service is fully isolated with its own database.
Services communicate asynchronously via RabbitMQ and synchronously via REST only where strictly necessary.

## Stack
- PHP 8.3, Laravel 11, Symfony 7
- MySQL (per service), Redis (cart + cache), Elasticsearch (search)
- RabbitMQ (async messaging)
- Docker + Docker Compose
- GitHub Actions (CI/CD)

## Repo structure
```
/services
  /auth-service           Symfony 7
  /user-service           Laravel 11
  /catalog-service        Laravel 11
  /cart-service           Laravel 11
  /order-service          Symfony 7
  /payment-service        Symfony 7
  /notification-service   Laravel 11
  /analytics-service      Symfony 7
/shared
  /php-common             Composer package: DTOs, events, RabbitMQ helpers
/docker-compose.yml
/CLAUDE.md                (this file)
```

## Hard rules
- Never import code directly between service folders. Use /shared/php-common only.
- Each service owns its own DB. No cross-service DB queries ever.
- The API Gateway handles JWT verification. Services trust the userId passed in headers.
- All API responses use the envelope: `{ data, meta, errors }`
- Events are named `domain.action` — e.g. `order.confirmed`, `payment.failed`

## Event map
```
user.registered           Auth       → [User, Notification]
catalog.stock_updated     Catalog    → [Search, Analytics]
cart.checkout_requested   Cart       → [Order]
order.confirmed           Order      → [Notification, Analytics, Payment]
order.cancelled           Order      → [Catalog (restock), Notification]
payment.completed         Payment    → [Order, Analytics]
payment.failed            Payment    → [Order, Notification]
```

## Docker commands
```bash
docker compose up -d                          # start all services
docker compose up -d cart-service redis       # start specific service
docker compose logs -f cart-service           # follow logs
```

## Per-service commands
See each service's own CLAUDE.md for test and artisan/console commands.

## Shared library
/shared/php-common is a local Composer package.
Install in each service via path repository in composer.json.
Contains: base DTOs, RabbitMQ publisher/consumer wrapper, JWT verifier middleware.

## CI/CD
GitHub Actions runs per-service on path filters.
Pipeline: lint → test → docker build.
Do not break the pipeline. Run tests locally before pushing.