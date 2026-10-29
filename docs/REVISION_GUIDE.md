# TukodTrack revision guide

## Contents
- [Deployment status](#deployment-status)
- [Revised workflows](#revised-workflows)
- [Database backup and restore](#database-backup-and-restore)
- [Release checklist](#release-checklist)
- [Documentation and presentation](#documentation-and-presentation)

## Deployment status
The existing system is deployed on Render, as confirmed by the project owner. This revision is a separate working branch; it has not yet been deployed or verified on the live service.

## Revised workflows
- Sales: review total and payment in a confirmation dialog before posting. Cancel preserves the entered sale.
- Voids: owner only; required reason, original receipt retained, inventory reversal and audit record in one transaction. Locking the sale prevents duplicate reversal.
- Supplier proof of delivery: attach a JPG, PNG or PDF (maximum 3 MB) when receiving a purchase. Proof is recorded with the receiver, timestamp, delivered quantities and remarks; download it from the purchase row. Older deliveries remain valid without proof. This feature covers incoming supplier deliveries, not customer dispatch.
- Custom roles: owner creates a named role based on cashier or stock permissions and assigns it through User access. Owner privileges cannot be assigned through a custom profile.
- Inventory: open Inventory → Inventory report; search products, filter low stock, print or export CSV. Snapshot uses current quantities; retail value is not purchase-cost valuation. Existing Reports → Activity provides dated stock movements.
- Notices: success/error messages remain visible on the current page until dismissed or navigation. No automatic timer.
- Passwords: at least 12 characters and confirmation; self-service change requires current password. New or reset employee passwords require replacement at next sign-in. Password changes invalidate other database sessions and remember tokens. Forgotten employee passwords can be reset by the owner; forgotten owner access uses `php artisan tukodtrack:reset-password` from a trusted shell configured for the correct database. No email reset is claimed without mail configuration.
- Alignment: common table styles vertically align cells and badges; numeric columns retain right alignment.

## Database backup and restore
New downloads use `pg_dump` and end in `.sql`. They include schema, data, users, password hashes and database-stored delivery proofs. Treat backups as confidential. PostgreSQL client 18 is installed by the Dockerfile; it supports servers through major version 18. A later server major version requires a matching/newer client.

SQL recovery uses PostgreSQL tools, not the web JSON uploader:
1. Create a separate empty PostgreSQL database, preferably the same major version as the source, and take a fresh backup of production.
2. Configure standard `PGHOST`, `PGPORT`, `PGUSER`, `PGPASSWORD`, `PGDATABASE` and `PGSSLMODE` environment variables for that recovery database. Do not paste credentials into source control.
3. Run `psql -X --set ON_ERROR_STOP=on --single-transaction --file=tukodtrack-backup.sql` with a client compatible with the dump.
4. Verify accounts, stock totals, sales, proofs and sequence-generated new records in the recovered copy before switching the application connection.
5. Clear sessions in the restored database and require users to sign in again.

The existing JSON preview/restore remains labeled as a legacy importer for earlier backups. It does not restore accounts or role definitions. Do not upload SQL to that importer. SQL files are not executed from browser uploads.

Reference: https://www.postgresql.org/docs/18/app-pgdump.html

## Release checklist
1. Review the branch and take a Neon backup before applying migrations.
2. Build the Docker image and test on a separate database. Do not run tests against production.
3. Startup runs existing migrations plus the additive roles/proof/password migration. Existing accounts and transactions are preserved.
4. Test sale cancellation/completion; cashier denial of void; owner void and duplicate submission; partial receiving with proof; custom roles; temporary password replacement; CSV/print; SQL backup and recovery on a separate database.
5. Confirm the Render release is healthy before merging/deploying to production.

## Documentation and presentation
The main manuscript was not supplied with this repository. These are ready-to-apply editorial requirements, not a claim that it has been edited:
- Add an automatic table of contents using heading styles; update it after pagination. “No table of contents” is treated as a missing-navigation issue.
- Keep each figure and caption together; insert images inline, set captions to Keep with next/previous as appropriate, and move an oversized figure to its own page.
- Prevent table rows splitting across pages; repeat header rows. Render the final document and check every page.
- Use the ERD and data dictionary in `ERD.md`, generated from the migrated schema.
- State: “TukodTrack is deployed on Render with a Neon PostgreSQL database.” Distinguish the existing deployment from revision rollout status.
- Presenters face the audience, use the screen as a visual aid, and demonstrate each revised workflow with test data.
