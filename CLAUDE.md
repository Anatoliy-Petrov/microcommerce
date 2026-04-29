# PHP E-Commerce Microservices

## Architecture
Monorepo with 8 services. Each service has its own DB and communicates via RabbitMQ.
Never import directly between service folders — only through /shared/php-common.

## Services
- auth-service       (Symfony 7) — JWT authentication
- user-service       (Laravel 11) — user profiles
- catalog-service    (Laravel 11) — products, categories, stock
- cart-service       (Laravel 11) — Redis-backed, no SQL
- order-service      (Symfony 7) — Workflow state machine
- payment-service    (Symfony 7) — Stripe integration
- notification-service (Laravel 11) — email/SMS via queues
- analytics-service  (Symfony 7) — event consumption

## Stack
PHP 8.3, Laravel 11, Symfony 7, MySQL (per service), Redis, RabbitMQ, Elasticsearch, Docker Compose

## Conventions
- All API responses: { data, meta, errors } envelope
- Events named as: domain.action (e.g. order.confirmed, payment.failed)
- Shared DTOs and RabbitMQ helpers live in /shared/php-common
- Each service has its own .env and Dockerfile

## Commands
- Start all: docker compose up -d
- Run tests: docker compose exec <service> php artisan test (Laravel) or bin/phpunit (Symfony)