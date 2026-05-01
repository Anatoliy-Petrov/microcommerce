# User Service

## What this service does
Manages user profiles. Stores display name, avatar, address book, and preferences.
Does NOT handle authentication — that is auth-service's job.
Listens for `user.registered` to create the initial profile row.

## Framework & stack
- Laravel 11
- MySQL (user_db)
- Local disk or S3 for avatar storage (Laravel Filesystem)

## Run commands
```bash
docker compose exec user-service php artisan migrate
docker compose exec user-service php artisan test
docker compose exec user-service php artisan tinker
docker compose exec user-service php artisan queue:work
```

## Endpoints
```
GET    /users/{id}              Get public profile
GET    /users/{id}/private      Get full profile (own user only)
PUT    /users/{id}              Update profile fields
POST   /users/{id}/avatar       Upload avatar image
DELETE /users/{id}/avatar       Remove avatar
GET    /users/{id}/addresses    List saved addresses
POST   /users/{id}/addresses    Add address
PUT    /users/{id}/addresses/{addrId}   Update address
DELETE /users/{id}/addresses/{addrId}  Delete address
```

## Events consumed
- `user.registered` → create profile row with default values

## Events published
- None. User service is a leaf node in the event graph.

## Database tables
- `profiles` — id (UUID, same as auth userId), display_name, bio, avatar_url, created_at, updated_at
- `addresses` — id, user_id, label, line1, line2, city, postcode, country, is_default

## Important rules
- The `id` in profiles matches the userId from the JWT. Do not generate a new ID.
- Never store passwords, tokens, or auth data here.
- Avatar uploads: validate mime type (jpg/png/webp only), max 2MB, resize to 400x400 on upload.
- Soft-delete profiles when an account is closed — never hard delete.

## Authorization
Middleware reads `X-User-Id` header (set by API Gateway after JWT validation).
Users can only modify their own profile. Admins can read any profile.
No JWT parsing in this service.

## Testing
```bash
docker compose exec user-service php artisan test --parallel
```
Use SQLite in-memory for unit tests (DB_CONNECTION=sqlite, DB_DATABASE=:memory:).
Factory in /database/factories/ProfileFactory.php.
