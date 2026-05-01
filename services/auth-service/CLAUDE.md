# Auth Service

## What this service does
Issues and validates JWT tokens. Handles registration, login, logout, and token refresh.
This is the only service that handles passwords and credentials.
All other services trust the `X-User-Id` and `X-User-Email` headers set by the API Gateway.

## Framework & stack
- Symfony 7
- MySQL (auth_db) — stores users, refresh tokens, revoked tokens
- LexikJWTAuthenticationBundle — JWT issuance
- Redis — token blacklist (fast revocation lookups)

## Key dependencies
```bash
composer require lexik/jwt-authentication-bundle
composer require symfony/security-bundle
composer require predis/predis
```

## Run commands
```bash
docker compose exec auth-service php bin/console cache:clear
docker compose exec auth-service php bin/phpunit
docker compose exec auth-service php bin/console doctrine:migrations:migrate
```

## Endpoints
```
POST /auth/register       Create user, publish user.registered event
POST /auth/login          Validate credentials, return access + refresh tokens
POST /auth/refresh        Swap refresh token for new access token
POST /auth/logout         Blacklist the current access token
GET  /auth/validate       Verify token (called by API Gateway only)
```

## JWT config
- Access token TTL: 15 minutes
- Refresh token TTL: 30 days
- Algorithm: RS256 (RSA key pair, never HS256)
- Keys live in /config/jwt/ — never commit private key, use Docker secrets

## Events published
- `user.registered` → payload: { userId, email, createdAt }

## Database tables
- `users` — id (UUID), email, password_hash, created_at, updated_at
- `refresh_tokens` — id, user_id, token_hash, expires_at, revoked_at
- No other tables. Auth service does not store profile data.

## Security rules
- Passwords: bcrypt cost 12 minimum
- Refresh tokens: stored as SHA-256 hash, never plain
- Brute force: rate limit login to 5 attempts per minute per IP (handled at gateway, but add Symfony rate limiter as defence-in-depth)
- Never log passwords or tokens

## Testing
```bash
docker compose exec auth-service php bin/phpunit --testdox
```
Use in-memory SQLite for unit tests. Use a dedicated auth_test_db for integration tests.
Fixtures in /tests/fixtures/. Always reset DB state between tests.