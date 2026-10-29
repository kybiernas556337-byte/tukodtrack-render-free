#!/usr/bin/env bash
# Restore a TukodTrack PostgreSQL .sql backup into a NEW, EMPTY recovery database.
# Never run this command on an existing production/staging database.
set -euo pipefail
if [[ $# -ne 1 || ! -f "$1" ]]; then
  echo "Usage: RECOVERY_DB_URL='postgresql://...' bash scripts/restore-sql-to-empty-db.sh backup.sql" >&2
  exit 2
fi
: "${RECOVERY_DB_URL:?Set RECOVERY_DB_URL to a NEW EMPTY PostgreSQL recovery database}"
command -v psql >/dev/null || { echo "PostgreSQL psql client required" >&2; exit 2; }
if [[ "$(head -c 16 "$1")" != "--"* ]]; then
  echo "File does not appear to be a plain SQL PostgreSQL dump." >&2
  exit 2
fi
# A dump creates tables and sequences; do not try to import into an occupied database.
existing="$(psql "$RECOVERY_DB_URL" -X -A -t -v ON_ERROR_STOP=1 -c "SELECT count(*) FROM pg_catalog.pg_tables WHERE schemaname='public'")"
if [[ "$existing" != "0" ]]; then
  echo "REFUSED: destination has $existing public tables. Use a new EMPTY recovery database." >&2
  exit 3
fi
echo "Destination is empty. Importing SQL backup in one transaction."
psql "$RECOVERY_DB_URL" -X -v ON_ERROR_STOP=1 --single-transaction -f "$1"
echo "Import finished. Verify tables and row counts before connecting any application."
