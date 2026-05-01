# Notification Service

## What this service does
Sends transactional emails and SMS messages triggered by domain events.
Pure consumer — it listens to events from other services and sends notifications.
Does not expose any public HTTP endpoints (internal health check only).

## Framework & stack
- Laravel 11
- MySQL (notification_db) — notification log and preferences
- Laravel Queues + Horizon — processes RabbitMQ messages
- Laravel Mail (Mailgun or SMTP in dev)
- Vonage or Twilio SDK — SMS (optional)

## Run commands
```bash
docker compose exec notification-service php artisan migrate
docker compose exec notification-service php artisan test
docker compose exec notification-service php artisan horizon
docker compose exec notification-service php artisan queue:work --queue=notifications
```

## Endpoints
```
GET  /health                    Health check (internal only, not exposed via gateway)
GET  /notifications/{userId}    List notification history for a user
PUT  /notifications/preferences Update user notification preferences
```

## Events consumed and notifications sent
| Event                  | Notification             | Channel     |
|------------------------|--------------------------|-------------|
| `user.registered`      | Welcome email            | Email       |
| `order.confirmed`      | Order confirmation       | Email + SMS |
| `order.cancelled`      | Cancellation notice      | Email       |
| `order.shipped`        | Shipping confirmation    | Email + SMS |
| `payment.failed`       | Payment failed alert     | Email       |

## Database tables
- `notification_log` — id, user_id, event_type, channel, recipient, subject, status, sent_at, error
- `notification_preferences` — user_id (PK), email_enabled, sms_enabled, sms_number, updated_at

## Mail templates
Blade templates in /resources/views/mail/.
One Mailable class per notification type in /app/Mail/.
Always use queued Mailables — never send synchronously.

## Important rules
- Always check notification_preferences before sending SMS.
- Log every send attempt in notification_log, including failures.
- Failed sends: retry up to 3 times with exponential backoff, then log as permanently_failed.
- Never throw exceptions that kill the queue worker — catch, log, and move on.
- Do not store email content in the log — only metadata (subject, recipient, status).
- Unsubscribe links must be in every marketing email (not required for transactional).

## Mail config (env vars)
```
MAIL_MAILER=smtp
MAIL_HOST=mailpit          # Mailpit in dev (catches all mail locally)
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@shop.local
MAIL_FROM_NAME="My Shop"
```

## Testing
```bash
docker compose exec notification-service php artisan test
```
Use `Mail::fake()` and `Notification::fake()` in all tests.
Assert that the correct Mailable was queued — do not actually send email in tests.
