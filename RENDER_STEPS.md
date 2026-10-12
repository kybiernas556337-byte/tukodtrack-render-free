# TukodTrack: Render deployment

This is an overlay for the exact project you uploaded, not a replacement project.
It preserves the app's interface and business code. It adds Docker/Apache hosting,
MySQL configuration, migrations, HTTPS proxy support, and safe initial-owner creation.

## 1. Copy files
Extract this ZIP and copy its contents into C:\xampp\htdocs\tukodtrack, next to
artisan and composer.json. Merge the bootstrap folder and replace bootstrap/app.php.
Keep your existing .env on your PC; it is excluded from the Docker image.

## 2. Push the deployment files
In your VS Code terminal, run:

    git add Dockerfile .dockerignore render.yaml deploy bootstrap/app.php RENDER_STEPS.md
    git commit -m "Add Render deployment"
    git push origin main

## 3. Create a Render Blueprint
Open https://dashboard.render.com and sign in with GitHub.
Choose New > Blueprint, connect yramata558747-lab/tukodtrack, and select main.
Render reads render.yaml and prepares BOTH services.
Review the displayed price before deploying: this file selects a paid Starter web
service, a paid Starter MySQL private service, and a persistent database disk.
It is NOT an entirely free deployment. You have not purchased anything by downloading these files.

## 4. Fill the four requested values
- APP_KEY: copy your local .env APP_KEY value including base64: (only the value).
  Keep the same key on later deployments; never publish it in GitHub.
- INITIAL_OWNER_NAME: your chosen owner display name.
- INITIAL_OWNER_EMAIL: the email you want to use to sign in.
- INITIAL_OWNER_PASSWORD: a unique password with at least 12 characters.

Database connection settings and random database passwords are wired automatically.
APP_URL uses Render's supplied public URL at startup. For a later custom domain,
set APP_URL to that complete HTTPS URL in the web service environment.

## 5. Deploy and log in
Click Deploy/Apply after reviewing the service charges. Watch each service's logs.
The website waits for MySQL, runs migrations, creates the first owner only when
there are no users, then starts Apache. It does not run your old DatabaseSeeder.
Open the web service's https://...onrender.com address and sign in using the
INITIAL_OWNER_EMAIL and INITIAL_OWNER_PASSWORD you supplied.
After successful login, remove INITIAL_OWNER_PASSWORD from the web service's
Environment settings. Existing accounts are never reset by this deployment.

## 6. Verify the app
Check login, add a test product, add a supplier, record a purchase/delivery,
and confirm the inventory changes. Log out and log back in. Restart the web
service and confirm records remain in MySQL.

## Existing records
This setup creates a NEW database. It does NOT copy your XAMPP database.
Keep your local database intact. If you need existing sales, stock, and accounts,
export the full database through phpMyAdmin (SQL, including the migrations table)
and arrange an import into the Render MySQL service before using the live app.
Do not commit that export or your .env to GitHub. Do not use migrate:fresh.
Your app's JSON backup is NOT a full database migration: it omits account passwords
and restoring it against different user IDs can misassociate historical records.

## Storage
MySQL data persists on its disk. The web service has no persistent disk: temporary
restore previews disappear on restart, so finish a restore without redeploying.
If you later add permanent file uploads, configure object storage or a web disk.

## Troubleshooting
- Build failure: open the web service build logs and share the first error.
- MySQL unavailable: confirm MySQL is running in the same region and inspect its logs.
- APP_KEY error: confirm the full base64: value was copied without quotes or spaces.
- Login failure: use the new initial-owner credentials, not the old seeded account.
- 500 error: inspect web logs; leave APP_DEBUG=false on the public website.
- Do not run php artisan db:seed: your existing seeder resets its fixed account password.

## Validation and limits
The configuration was checked against your uploaded Laravel 12 source and Render's
Blueprint documentation. Shell/YAML checks were performed. Docker and PHP are not
installed in the preparation environment, so an actual image build, PHP execution,
and live Render deployment were not performed. Render's first build is the runtime check.

Official documentation:
https://render.com/docs/blueprint-spec
https://render.com/docs/deploy-mysql
https://render.com/docs/docker
