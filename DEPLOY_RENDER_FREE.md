# TukodTrack: Render Free + Neon Free

This is a corrected source copy. Keep the original ZIP separately. The web container uses PHP 8.2, Apache, and PostgreSQL. The Blueprint creates only one Free web service; Neon is created separately. No production credentials, local database, Git history, vendor directory, or node_modules are included.

## 1. Create the Neon database

Sign in at https://console.neon.tech and create a project named tukodtrack using the Free plan. Choose a nearby region (Singapore if available); use PostgreSQL 17 if offered. Open Connect. Turn connection pooling OFF and copy only the direct PostgreSQL connection URL, not a psql command. Keep sslmode=require and any supplied query parameters. Use the direct URL for this guide because startup runs migrations and configures the connection timezone. Never post the URL publicly.

## 2. Prepare the corrected project on your Mac

Extract TukodTrack_Render_Free_Neon.zip. In Terminal type `cd `, drag its tukodtrack folder into Terminal, press Enter. Confirm `ls artisan Dockerfile render.yaml` finds all three files.

Check PHP and Composer:

```bash
php -v
composer --version
php -r 'echo implode(", ", PDO::getAvailableDrivers()), PHP_EOL;'
```

PHP must be at least 8.2 and the driver list must contain pgsql. If PHP or Composer is missing, use Homebrew `brew install php composer`, open a new Terminal, and check again. If pgsql is missing, use the Homebrew PHP installation rather than a different PHP installation that lacks it (`which php` identifies the selected executable).

Install dependencies and create the private configuration:

```bash
composer install
cp .env.render.example .env
```

Open the folder in VS Code. Edit `.env`: replace DB_URL with the direct Neon URL (keep quotes in this local file). Do not edit `.env.render.example` to contain real credentials. Leave APP_KEY empty for now. APP_URL will later be replaced with the actual Render HTTPS address.

For a NEW deployment only:

```bash
php artisan config:clear
php artisan key:generate
php artisan migrate --force
php artisan tukodtrack:owner
```

Enter your name, email and a unique password with at least 12 characters. Password entry is hidden. Keep APP_KEY from `.env` for the Render settings. The owner command refuses to overwrite an existing owner. Never run migrate:fresh on the operational database. Never generate a new APP_KEY for an existing installation.

## 3. Upload to your own GitHub repository

Create a NEW empty repository at https://github.com/new, named tukodtrack-render-free. A private repository is fine. Leave README, license and .gitignore initialization unchecked. In your corrected project Terminal:

```bash
git init
git branch -M main
git add .
git status --short
git diff --cached --name-only
```

Before committing, ensure no real `.env`, vendor, node_modules, database .sqlite/.sql files, backups or logs are staged. `.env.example` and `.env.render.example` are safe templates. If a real credential file was accidentally staged, unstage it before committing.

```bash
git commit -m "Deploy TukodTrack on Render Free with Neon"
git remote add origin https://github.com/YOUR_USERNAME/tukodtrack-render-free.git
git push -u origin main
```

Replace YOUR_USERNAME with your account name. Use GitHub's browser authentication or a personal access token if Git prompts; GitHub account passwords do not work for HTTPS Git authentication. Open the repository and confirm Dockerfile, artisan, render.yaml and deploy/ appear at the top level.

## 4. Create a Render Free web service

Sign in to your EXISTING account at https://dashboard.render.com. If the older deployment is another project, create a new service. If TukodTrack is already deployed, update that service deliberately rather than creating a duplicate; retain its existing APP_KEY and database.

Choose New -> Web Service -> Git Provider, connect the new repository, and select:

| Setting | Value |
| --- | --- |
| Name | tukodtrack (or a unique available name) |
| Branch | main |
| Region | Singapore |
| Language/runtime | Docker |
| Root Directory | blank |
| Dockerfile Path | ./Dockerfile |
| Instance Type | Free ($0) |
| Docker Command | blank; use the Dockerfile CMD |
| Health Check Path | /up |

Do not create a Render MySQL service, paid disk, background worker or paid database for this setup. Manual Web Service creation does not apply render.yaml settings automatically; enter every environment variable below yourself.

## 5. Configure Render environment variables

Use plain values in the Render form, without the enclosing quotes used in `.env`:

| Key | Value |
| --- | --- |
| APP_NAME | TukodTrack |
| APP_ENV | production |
| APP_DEBUG | false |
| APP_KEY | full base64:... value from your private .env |
| APP_TIMEZONE | Asia/Manila |
| DB_CONNECTION | pgsql |
| DB_URL | direct Neon PostgreSQL URL with TLS query parameters |
| SESSION_DRIVER | database |
| SESSION_SECURE_COOKIE | true |
| CACHE_STORE | database |
| QUEUE_CONNECTION | sync |
| LOG_CHANNEL | stderr |
| LOG_LEVEL | warning |

For the initial deployment, APP_URL may be omitted: the startup script reads the actual RENDER_EXTERNAL_URL that Render provides. Once Render displays the final URL, add APP_URL with that exact HTTPS address and redeploy. Do not enter a guessed URL. Do not use INITIAL_OWNER variables; the owner was created from your Mac in step 2.

Select Deploy Web Service. Render builds the assets and dependencies, connects to Neon, runs migrations and starts Apache on its assigned PORT. The correct startup log includes `PostgreSQL connection ready.`

## 6. Open the site and finish configuration

Once status is Live, copy the actual https://...onrender.com URL. Add APP_URL in Render Environment -> Save/redeploy. Update APP_URL in your local private .env to the same value.

Open the website and log in using the owner email/password you created. In User Access, create your cashier and stock personnel accounts. Add the store's products, suppliers and opening balances manually; the original ZIP's local data is not imported. Sample loading is hidden and blocked in production.

## 7. Verify before entering operational records

1. Add a product with opening quantity 10 and price 100.
2. Sell 2 units, receive payment 250: remaining stock 8, total 200, change 50; open/reprint receipt.
3. Attempt a sale exceeding stock and confirm it does not change stock or save a sale.
4. Create a purchase for 5 units; stock stays unchanged until receiving. Receive 3, then 2: partial then received status and stock 13.
5. Submit a count of 12 as stock personnel; stock stays 13 until the owner approves, then becomes 12.
6. Search for a product with different capitalization and confirm it appears.
7. Download a JSON backup. Verify restoration ONLY on a separate test installation; restoration replaces business records. Check restore above 200 rows, rejected invalid backups, retained accounts and creation of a new record after restore.
8. Verify daily totals and receipts use Asia/Manila, HTTPS login works, and the sample endpoint is blocked.
9. Redeploy and confirm records remain in Neon.

The included tests/Feature/DeploymentWorkflowsTest.php covers core workflows, rollback, search, over-200-row restore, PostgreSQL sequences, invalid-restore rollback, throttling and production sample controls. To run PostgreSQL tests, use a completely separate database whose name ends in `_test`, configure its URL locally and run `vendor/bin/phpunit -c phpunit.pgsql.xml`. It refuses other database names. Tests reset that test database; never point them at operational Neon data. Clear config cache first.

## 8. Backups and free-tier behavior

Download the owner's JSON backup weekly and before redeploying. Save backups outside Render. Before schema changes, make a full pg_dump export using PostgreSQL client tools from your Mac; keep the APP_KEY, other configuration and matching source code separately. JSON restores preserve existing accounts and expect their IDs to match this installation; they are not a full disaster-recovery export for a new database with different accounts. Use pg_dump for full recovery.

Render Free sleeps after 15 minutes without traffic; the next visit can take about a minute to load. Its 750 monthly free hours are shared by all Free web services in the same workspace, including any earlier deployments. Check Billing -> included usage and keep both platforms on Free; added payment methods can allow overage charges for bandwidth/build usage. No paid disk is necessary because persistent records are on Neon. A pending restore preview uses a temporary local file and expires if Render restarts; upload the backup again if needed.

Neon's published Free plan includes a short instant-restore history window (currently up to six hours, capped), unlike the earlier assumption that no restore history was available. Keep your own backups regardless. Free limits and availability can change; check the dashboards before deployment.

## Common errors

- `could not find driver`: check pdo_pgsql exists in your local PHP; Render must rebuild from this Dockerfile.
- `PostgreSQL unavailable`: check DB_URL, its TLS query parameters and Neon project status. Copy a connection URL, not `psql '...'`.
- `relation ... does not exist`: migrations did not run successfully; check deployment logs or run `php artisan migrate --force` locally with the correct DB_URL.
- `No application encryption key`: APP_KEY is missing or incomplete; use the same key generated for this installation.
- Login redirects or 419: check exact HTTPS APP_URL, APP_KEY, proxy configuration and database sessions; clear browser cookies after fixing settings.
- Assets fail: inspect Node build logs; the Docker build supplies public/build.
- Owner already exists: sign in with that owner; this command intentionally does not reset passwords.

## Verification of this package

Static YAML, JSON, shell syntax and deployment settings were checked. PHP/PostgreSQL execution and Docker builds could not run in the authoring environment because PHP, PostgreSQL and Docker were unavailable and package installation could not complete. The supplied tests must be run on a developer/test environment; this package is not represented as having passed PostgreSQL integration tests.
