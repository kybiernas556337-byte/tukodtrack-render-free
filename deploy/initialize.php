<?php
// Use Laravel's PostgreSQL URL parsing and TLS settings; never print credentials.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'pgsql') {
    fwrite(STDERR, "This deployment requires DB_CONNECTION=pgsql.\n");
    exit(1);
}
for ($attempt = 0; $attempt < 30; $attempt++) {
    try {
        Illuminate\Support\Facades\DB::connection()->getPdo();
        echo "PostgreSQL connection ready.\n";
        exit(0);
    } catch (Throwable $e) {
        Illuminate\Support\Facades\DB::purge();
        if ($attempt === 29) {
            fwrite(STDERR, "PostgreSQL unavailable. Check DB_URL and Neon's database status.\n");
            exit(1);
        }
        sleep(2);
    }
}
