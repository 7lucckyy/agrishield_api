# AgriShield owner handoff

These are external inputs, not software-completion claims. Do not enable a farmer pilot until the security, field and operational checks below are signed off.

| What is needed | Why | Where to configure | Secret/account required | How to verify |
| --- | --- | --- | --- | --- |
| PostgreSQL 16 with PostGIS, backups and a production-like staging database | Spatial storage and restore assurance | `DB_*`, `REQUIRE_POSTGIS=true`, deployment runtime | Database administrator/host | Run migrations and full CI suite against PostGIS; restore a backup into isolated staging and compare row counts and spatial queries. |
| Real farming/weather/satellite provider agreement and credentials | Fake providers must not serve live agronomic decisions | Production environment provider settings and adapter configuration | Provider contract/API key | Contract and live smoke tests with provenance, rate limits and failure handling. |
| Map provider and offline tile terms | Licensed basemap and lawful offline use | Flutter map adapter/environment | Map account/license | Confirm approved terms, test online and offline coverage on pilot devices. |
| Speech/diagnosis providers and approved Hausa text | Live STT/TTS/AI service and trustworthy farmer wording | Backend provider environment, mobile localization assets | Service keys; native Hausa reviewer | Live provider smoke tests and signed native-language review. |
| Private object storage and retention policy | Uploaded images/audio need durable private storage and lifecycle controls | Set `PRIVATE_FILESYSTEM_DRIVER=s3` with `AWS_*` variables and an approved media migration/retention policy; the adapter is locked and the local default remains private | Bucket, IAM credentials, privacy decision | Upload, authorize, download, revoke and restore sample media; verify no public object access. Voice processing reads streams, but live remote storage is unverified. |
| Push/SMS sender and notification consent policy | External delivery cannot be demonstrated without a sender and consent | Notification provider environment and user preferences | Vendor account/credentials | Send consented test messages, observe retries and opt-out. |
| Android signing and Play Console | A distributable release must be owner-signed | `ANDROID_KEYSTORE_PATH`, `ANDROID_KEYSTORE_PASSWORD`, `ANDROID_KEY_ALIAS`, `ANDROID_KEY_PASSWORD`; `APP_ENV=production` and `API_BASE_URL` dart defines | Private keystore and Play account | Build an AAB; inspect certificate; install through an internal testing track. Never commit keys. |
| Apple signing | iOS distribution requires Apple identity and provisioning | Xcode signing settings; same API dart defines | Apple Developer account/certificates | Archive and install through TestFlight. |
| Production host, TLS, Redis, queue workers, scheduler, monitoring and backups | API and asynchronous jobs need reliable operations | Laravel `.env`, process supervisor, deployment platform | Host, TLS/domain, monitoring accounts | Staging deployment, health checks, queue failure drill, backup/restore drill and alert receipt. |
| Finance partner and legal/privacy approval | Lending, evidence retention, export and deletion need lawful business rules | Finance provider settings and data policy | Partner contract; legal approval | Contract tests plus signed policy and partner acceptance. |
| Agronomist, security and pilot sign-off | No code test validates agronomic safety or real-world usability | [Field validation plan](FIELD_VALIDATION_PLAN.md) | Qualified reviewers and consenting farmers | Record evidence, issue log, acceptance criteria and explicit approvals. |

## Engineering verification still required

- Run the GitHub Actions PostGIS job and verify the required PostGIS assertion; the local PostgreSQL instance used for development lacks PostGIS.
- The project's bundled Flutter SDK passed analysis, all 16 tests and an Android debug APK build locally. CI must repeat these checks on the release branch. An unsigned release attempt was confirmed to fail with the expected missing-signing-credentials error; an owner-signed AAB is still required.
- Keep `composer audit` in CI and rerun it before each deployment. The two previously reported Laravel/Flysystem advisories are fixed in the current lockfile; a connected local audit now reports zero advisories.
- The first upgraded mobile launch discards legacy unowned cached/outbox rows, removes old unowned queued-media files in the legacy outbox root, and removes the old local session. Account-owned media subdirectories are preserved. This prevents cross-account attribution errors but can lose unsent pre-upgrade submissions. Communicate the upgrade policy before pilot rollout; retain an opt-in recovery path only if ownership can be safely established.
- Decide whether the account-scoped SQLite database also requires full encryption at rest. Tokens and account-scoped drafts use platform secure storage, but SQLite farm/outbox metadata is not yet encrypted. Do not put finance-sensitive data in this cache.

Current status: **not approved for a controlled farmer pilot or public production**. These checks are prerequisites, not retrospective confirmations.
