# Changes from the supplied ZIP

- Replaced paid MySQL Blueprint with a single Render Free web service; database is external Neon PostgreSQL.
- PHP 8.2 Docker image installs pdo_pgsql, mbstring, zip, bcmath and opcache; assets built with Node 22.
- PostgreSQL/TLS-aware startup readiness check, automatic migrations and Render-assigned Apache port.
- Removed automatic owner creation from startup; added interactive tukodtrack:owner command without overwrite behavior.
- Asia/Manila application and PostgreSQL connection timezone.
- Database session/cache and stderr logging configuration.
- PHP upload limit 50M, total request limit 55M and matching Apache 55 MiB limit.
- Login rate limits per email/IP and per IP; production sample-data server guard and hidden button.
- Case-insensitive Laravel whereLike searches (PostgreSQL ILIKE).
- Restore keeps foreign keys active, deletes children first, inserts parents first, locks business tables on PostgreSQL and resets identity sequences.
- Stronger backup table-shape checks and generic restore errors; private runtime files excluded.
- Added workflow tests and step-by-step guide; static checks passed, execution tests not run here.

The PDF itself was not changed. Its System Architecture paragraph still describes SQLite and should be corrected separately. Its assumption about no Neon Free restore history is outdated according to current Neon documentation.
