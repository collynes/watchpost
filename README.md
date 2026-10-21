# Watchpost

Self-hosted security event ingestion, alerting, and daily reporting. Watchpost uses Laravel 13 and PostgreSQL; Supabase is a supported managed PostgreSQL host. The dashboard is intended for a small internal operations team.

## Implemented

- Source-scoped bearer credentials stored as SHA-256 hashes.
- Single and atomic batch ingestion (500 events, 1 MiB maximum), immutable event records, source-scoped retry protection and conflict detection.
- Default event-type severity, per-source overrides, SEV 1–2 alerts, super-admin creation escalation, nested metadata credential redaction.
- Operator login, source/activity view, filters, and alert acknowledgment/resolution with operator attribution.
- Daily snapshots for the previous completed calendar day in Europe/Amsterdam, scheduled at 07:00; queued digest and high-severity alert emails with retries and delivery markers.
- Private `watchpost` schema and RLS without client policies. Supabase public API roles cannot read these tables.

## Local setup

```sh
composer install
cp .env.example .env
php artisan key:generate
# Configure your database credentials in .env, then:
php artisan watchpost:prepare-database
php artisan migrate --force
php artisan watchpost:source app-one "Application One"
php artisan watchpost:source mail-alerts "External Mail Alerts" --method=email
php artisan watchpost:operator operator@example.com "Operator"
php artisan serve --host=127.0.0.1 --port=8080
```

Requirements: PHP 8.3–8.5, Composer, and PostgreSQL 15 or later. SQLite is used by the automated test suite. For Supabase, use a dedicated project and a database connection pooler host from that project's Connect settings; put the project reference and database password in your local `.env` only.

The source command prints a credential once. Store it in Daryeel's environment; do not put it in browser JavaScript. The operator command prompts privately for a password. Alternatively, `--generate` writes a generated password to `storage/app/private/operator-credentials.txt` with owner-only permissions; it refuses to overwrite an existing file. No public registration exists. The email source registration reserves its identity; it does not yet fetch mail.

For development workers run `php artisan queue:work` and `php artisan schedule:work` in separate terminals. `php artisan watchpost:digest --date=2026-10-14` stores a snapshot without sending. Add `--send` only after configuring delivery.

## Ingestion contract

`POST /ingest/v1/events`, JSON body, `Authorization: Bearer <source credential>`:

```json
{
  "schema_version": "1",
  "event_id": "source-generated-stable-uuid",
  "source_id": "daryeel-portal",
  "event_type": "auth.login_failed",
  "timestamp": "2026-10-15T10:00:00Z",
  "actor_ip": "192.0.2.1",
  "actor_email": "victim@example.com",
  "count": 1,
  "outcome": "failure",
  "message": "Failed login"
}
```

`event_id` is now **required** in addition to the original specification. Generate it once at the source and retain it across delivery retries. Repeating the same ID and payload returns 200 with `duplicates: 1`; changing a payload under an existing ID returns 409. New events return 201 with `accepted`, `duplicates`, `event_ids`, and `alerts_created`. For batches, use `/ingest/v1/events/batch` with `{"events": [...]}`. Any invalid item or conflicting ID rejects the whole batch. Sources cannot submit events for another source. Timestamps must be UTC with `Z`; future timestamps beyond five minutes are rejected.

Initial severity defaults follow the specification's event-type table. A submitted severity can raise urgency but cannot lower the configured default. Low-severity events are stored individually; retry protection is separate from behavioral aggregation. Correlation and frequency escalation are not yet implemented.

Daryeel should dispatch a queued HTTP job with `Http::withToken(config('services.watchpost.token'))->timeout(10)->post(config('services.watchpost.url').'/ingest/v1/events', $payload)->throw()`. Construct the payload, event ID, and timestamp before dispatching the job; retain that payload across retries. Never delay the login request waiting for Watchpost.

## Mail and scheduling

Email is disabled in the supplied local configuration. Configure Gmail SMTP (`smtp.gmail.com`, port 587), username, App Password, sender and recipients; set `MAIL_MAILER=smtp` and `WATCHPOST_MAIL_ENABLED=true` to enable delivery. Restart workers after configuration changes. No mail is sent by migrations or source setup.

Digests use a local calendar day, not a rolling 24 hours. DST days can be 23 or 25 hours. Stored snapshots are immutable; later-arriving events do not rewrite an existing report. The alert list includes unresolved SEV 1–2 alerts from that report day. Individual events and future imported source summaries must remain separate to prevent double-counting.

Alert emails are rate-limited to 10 per minute globally and delayed through the queue. SMTP delivery is at-least-once: a process failure after provider acceptance but before saving the delivery marker can cause a duplicate. Inspect failures with `php artisan queue:failed`; retry with `php artisan queue:retry <id>`. Mail-disabled jobs skip delivery. Digest generation is not a substitute for ingestion health monitoring.

## VPS / Docker Compose

```sh
docker compose build
docker compose run --rm web php artisan watchpost:prepare-database
docker compose run --rm web php artisan migrate --force
docker compose up -d
```

Web binds only to `127.0.0.1:8080`; place an HTTPS reverse proxy in front. Set `APP_URL` to the HTTPS address, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, and keep `APP_DEBUG=false`. Configure only your proxy's IP as trusted before relying on forwarded headers. Worker, scheduler, and web use the same Supabase database queue/cache/session stores. No local PostgreSQL or Redis service is needed. `.env` is excluded from the image and source control. Database TLS uses `require`; use `verify-full` with the appropriate CA for authenticated certificate verification on the production host.

The deployment connection currently uses the database owner account. Provision a restricted server role before production deployment. Supabase Realtime is not enabled in this foundation: the dashboard requires refresh. Keep the private schema out of Supabase's exposed schemas until authenticated subscription policies are designed.

## Verification

```sh
php artisan test --compact
vendor/bin/pint --format agent
```

The default suite uses isolated in-memory SQLite. The PostgreSQL smoke test is skipped unless explicitly enabled with `WATCHPOST_POSTGRES_SMOKE=1 DB_CONNECTION=pgsql DB_DATABASE=postgres php artisan test tests/Feature/PostgresSmokeTest.php`; it rolls back its records and must run alone. PostgreSQL migrations are separately checked against the dedicated Supabase instance.

## Pending

- Gmail IMAP ingestion with durable message tracking and parser quarantine; sanitized sample messages and a Gmail App Password are still needed.
- Supabase-authenticated Realtime subscriptions, correlation, source management UI, retention jobs, and production VPS deployment.
- Integration code in the source applications is outside this repository.

## Contributing and security

See [CONTRIBUTING.md](CONTRIBUTING.md) for development and review guidance and [SECURITY.md](SECURITY.md) for vulnerability reporting. Do not commit `.env`, database files, operator credentials, source credentials, real event records, or unsanitized email samples.

## License

Watchpost is available under the [MIT License](LICENSE).
