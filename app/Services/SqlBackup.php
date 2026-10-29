<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class SqlBackup
{
    public function create(): string
    {
        if (DB::getDriverName() !== 'pgsql') {
            throw new \RuntimeException('SQL backups require the deployed PostgreSQL database.');
        }
        $c = DB::connection()->getConfig();
        $file = tempnam(sys_get_temp_dir(), 'tukod-sql-');
        $process = new Process(['pg_dump', '--format=plain', '--no-owner', '--no-privileges', '--file='.$file], null, [
            'PGHOST' => $c['host'], 'PGPORT' => (string) $c['port'], 'PGDATABASE' => $c['database'],
            'PGUSER' => $c['username'], 'PGPASSWORD' => $c['password'], 'PGSSLMODE' => $c['sslmode'] ?? 'require',
            'PGCONNECT_TIMEOUT' => '15',
        ]);
        $process->setTimeout(180);
        try {
            $process->mustRun();

            return $file;
        } catch (\Throwable $e) {
            @unlink($file);
            throw new \RuntimeException('SQL backup failed. Check database connectivity and the PostgreSQL client version.');
        }
    }
}
