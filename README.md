# TukodTrack

Laravel 12 inventory and sales system for Owner/Manager, Cashier and Stock Personnel.

For the complete free deployment instructions, read [DEPLOY_RENDER_FREE.md](DEPLOY_RENDER_FREE.md).

Deployment: Render Free Docker web service (PHP 8.2/Apache) and external Neon Free PostgreSQL, with Asia/Manila dates, database sessions/cache and stderr logs. Create the first owner from your developer machine using `php artisan tukodtrack:owner` after configuring DB_URL and running migrations.

Install dependencies with `composer install`; Docker builds front-end assets automatically. Keep real .env files, credentials and database backups private. Existing deployments must retain APP_KEY.

See the deployment guide's validation section for the included tests and verification limits.
