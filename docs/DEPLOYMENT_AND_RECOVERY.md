# AgriShield deployment and recovery runbook

This is a staging procedure, not evidence that production infrastructure or recovery has been validated. Owner-supplied secrets and infrastructure are listed in [HUMAN_ACTION_REQUIRED.md](HUMAN_ACTION_REQUIRED.md).

## Runtime contract

- Serve Laravel over TLS with `APP_ENV=production`, `APP_DEBUG=false`, a persistent `APP_KEY`, trusted proxy configuration and a dedicated PostgreSQL 16/PostGIS database. Set `REQUIRE_POSTGIS=true` in test/staging gates.
- Run Redis for queues/cache. Keep `retry_after` greater than the longest worker timeout. Run workers for `voice-assistance,diagnosis,sync,default` and a scheduler invoking `php artisan schedule:run` once per minute. Supervise and restart workers on deployment with `php artisan queue:restart`.
- Build web assets (`npm ci`, `npm run build`) and install PHP dependencies from the lock file (`composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader`). Cache configuration/routes/views only after production secrets are injected.
- Keep uploaded diagnosis and voice media private. Set `PRIVATE_FILESYSTEM_DRIVER=s3` with owner-supplied S3-compatible credentials, or use a durable shared private volume until the bucket is verified. The S3 adapter is installed and voice processing is stream-based, but no live bucket test has been performed. Do not use the public disk or a public bucket. A production multi-node deployment must not rely on node-local media.
- Set real provider drivers/credentials; application boot must reject fake providers in production. Confirm provider health and logs before releasing farmer traffic.
- Build Android with explicit HTTPS `API_BASE_URL`, `APP_ENV=staging|production` dart defines and owner-held signing environment variables. Never distribute the debug APK.

## Staging deployment gate

For a fresh local database, `docker compose up -d postgres redis` provisions PostgreSQL 16 with PostGIS in both `agrishield` and `agrishield_testing` via the init script. Set the `DB_TEST_*` values from `phpunit.xml`, then run the test suite with `REQUIRE_POSTGIS=true`. Docker is not installed on every developer machine; a non-PostGIS local run does not satisfy this gate. Existing Compose volumes do not rerun initialization: have a database administrator enable `postgis` in each existing database and migrate in a controlled maintenance window. Do not delete an existing volume to obtain the extension.

1. Create an isolated PostGIS database and run `CREATE EXTENSION IF NOT EXISTS postgis` as an authorized database administrator. Verify `SELECT postgis_version()`.
2. Run `php artisan migrate --force`, then verify farm and farm-section spatial columns and `php artisan route:list --path=api --except-vendor`.
3. Start workers and scheduler. Exercise an authenticated farm, field, voice and diagnosis flow using test data. Confirm media authorization, queue completion, provider failures and tenant isolation.
4. Run the full PostgreSQL/PostGIS test suite, PHPStan, Pint, Flutter analyze/tests, web build and Android build in CI. Review `composer audit` findings before deployment.
5. Verify TLS, secrets injection, log scrubbing, health endpoints, queue failure alerts and a restore rehearsal. Obtain privacy, agronomy and pilot-owner sign-off before real farmer enrollment.

## Backup

1. Record the deployed code revision, migration level, DB host/version and PostGIS version. Use an encrypted, access-controlled target outside the application host.
2. Take a consistent PostgreSQL custom-format backup with `pg_dump --format=custom --no-owner --no-acl --file=<approved-backup-path> <database-name>`. Do not put passwords in command arguments or shell history; use a secure connection file/secret manager.
3. Separately snapshot the private media volume or object bucket with versioning and an inventory. Capture the database/media time boundary and retention policy. A DB backup without media is not a recoverable diagnosis/voice record.
4. Encrypt, checksum and transfer the backup to the approved storage location. Record checksum, size, timestamp, responsible operator and retention expiry. Test access controls; do not publish backup URLs.

## Restore rehearsal

1. Use a disposable isolated environment. Never point a rehearsal at production or an existing user database.
2. Provision the same PostgreSQL major version and PostGIS extension. Restore using `pg_restore --no-owner --no-acl --dbname=<isolated-database> <approved-backup-path>`.
3. Restore the matching private-media snapshot to the isolated private disk/bucket. Inject non-production provider credentials or disable outbound jobs while inspecting recovered data.
4. Verify migration status, table counts, representative farm/field spatial queries (`ST_Covers`), media checksums and authorized media download. Confirm unrelated users cannot read the restored records.
5. Run a smoke test and record elapsed recovery time, data loss window, checksum results, failures and remediation. Only then consider the recovery objective demonstrated for that environment.

## Incident rollback

Stop ingress and queue workers before restoring data. Prefer rolling back the application artifact while preserving schema compatibility. Do not reverse migrations or restore an old database over new writes without an explicit data-loss decision and verified backup. Revoke compromised credentials, rotate secrets and document the event before resuming traffic.
