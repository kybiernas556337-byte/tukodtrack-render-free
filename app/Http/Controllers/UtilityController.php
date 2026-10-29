<?php
namespace App\Http\Controllers;

use App\Models\{ActivityLog, Category, Product, Setting, Supplier, User};
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{DB, Hash, Storage, Validator};
use Illuminate\Support\Str;

class UtilityController extends Controller
{
    private const TABLES = ['categories', 'products', 'suppliers', 'purchases', 'purchase_items', 'deliveries', 'delivery_items',
        'sales', 'sale_items', 'stock_adjustments', 'stock_movements', 'activity_logs', 'settings'];

    public function index()
    {
        return view('utilities.index', ['logs' => ActivityLog::with('user')->latest()->latest('id')->limit(50)->get()]);
    }

    public function backup()
    {
        abort_unless(DB::getDriverName() === 'pgsql', 422, 'SQL export requires PostgreSQL.');
        $connection = DB::connection()->getConfig();
        $database = $connection['database'] ?? null;
        abort_unless($database, 500, 'Database configuration is missing.');

        $command = [
            'pg_dump', '--format=plain', '--no-owner', '--no-privileges',
            '--inserts', '--dbname=' . $database,
        ];
        $environment = [
            'PGHOST' => $connection['host'] ?? 'localhost',
            'PGPORT' => (string) ($connection['port'] ?? '5432'),
            'PGUSER' => $connection['username'] ?? '',
            'PGPASSWORD' => $connection['password'] ?? '',
            'PGSSLMODE' => 'require',
        ];
        $process = new \Symfony\Component\Process\Process($command, null, $environment);
        $process->setTimeout(180);
        $process->run();
        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput();
            $type = str_contains($error, 'server version mismatch') ? 'client version mismatch' :
                (str_contains($error, 'password authentication failed') ? 'database authentication' :
                (str_contains($error, 'could not translate host name') ? 'database hostname' : 'PostgreSQL export error');
            report(new \RuntimeException('SQL backup failed (' . $type . '). Exit status: ' . $process->getExitCode()));
            return back()->with('error', 'SQL backup failed (' . $type . '). No database records were changed.');
        }
        ActivityLog::record('SQL backup downloaded');
        return response($process->getOutput(), 200, [
            'Content-Type' => 'application/sql; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="tukodtrack-backup-' . date('Ymd-His') . '.sql"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Step 1: read the chosen backup and show its date and size. Nothing is changed yet. */
    public function restorePreview(Request $r)
    {
        $r->validate(['backup' => 'required|file|max:51200']);
        $json = json_decode(file_get_contents($r->file('backup')->getRealPath()), true);
        if (!is_array($json) || ($json['app'] ?? null) !== 'TukodTrack' || !is_array($json['tables'] ?? null)
            || array_diff(self::TABLES, array_keys($json['tables']))
            || collect(self::TABLES)->contains(fn ($t) => !is_array($json['tables'][$t])
                || collect($json['tables'][$t])->contains(fn ($row) => !is_array($row)))) {
            return back()->with('error', 'That file is not a TukodTrack backup.');
        }
        $json['_uploaded_name'] = $r->file('backup')->getClientOriginalName();

        foreach (Storage::disk('local')->files('restore-pending') as $old) {          // clean up abandoned previews
            if (Storage::disk('local')->lastModified($old) < now()->subDay()->getTimestamp()) Storage::disk('local')->delete($old);
        }
        $token = Str::random(40);
        Storage::disk('local')->put("restore-pending/$token.json", json_encode($json));
        return view('utilities.restore', $this->previewData($token));
    }

    private function previewData(string $token): array
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{40}$/', $token), 404);
        $path = "restore-pending/$token.json";
        abort_unless(Storage::disk('local')->exists($path), 404, 'This restore session expired. Please select the backup file again.');
        $json = json_decode(Storage::disk('local')->get($path), true);
        $t = $json['tables'] ?? [];
        $created = Carbon::parse($json['created_at'] ?? now());
        $since = $created->copy()->setTimezone(config('app.timezone'))->toDateTimeString();

        return [
            'token' => $token, 'name' => $json['_uploaded_name'] ?? 'backup.json', 'created' => $created,
            'counts' => ['Products' => count($t['products'] ?? []), 'Suppliers' => count($t['suppliers'] ?? []), 'Purchases' => count($t['purchases'] ?? []),
                'Sales' => count($t['sales'] ?? []), 'Stock movements' => count($t['stock_movements'] ?? [])],
            'later' => ['Sales' => DB::table('sales')->where('created_at', '>', $since)->count(),
                'Purchases' => DB::table('purchases')->where('created_at', '>', $since)->count(),
                'Stock movements' => DB::table('stock_movements')->where('created_at', '>', $since)->count()],
        ];
    }

    /** Step 2: owner confirms with their password. Replaces business records; user accounts are NOT touched. */
    public function restore(Request $r)
    {
        $v = Validator::make($r->all(), ['token' => 'required|string', 'password' => 'required', 'acknowledge' => 'accepted'], [
            'password.required' => 'Enter your password to continue.',
            'acknowledge.accepted' => 'Please confirm that you understand the restore and the reconciliation.',
        ]);
        if ($v->fails()) return view('utilities.restore', $this->previewData((string) $r->token))->withErrors($v);
        if (!Hash::check($r->password, $r->user()->password)) {
            return view('utilities.restore', $this->previewData($r->token))->withErrors(['password' => 'Incorrect password. Management credentials are required to restore.']);
        }

        $data = $this->previewData($r->token);
        $json = json_decode(Storage::disk('local')->get("restore-pending/{$r->token}.json"), true);

        try {
            DB::transaction(function () use ($json) {
                // Keep foreign keys enforced. Delete children first, insert parents first.
                if (DB::getDriverName() === 'pgsql') {
                    DB::statement('LOCK TABLE ' . implode(', ', self::TABLES) . ' IN EXCLUSIVE MODE');
                }
                foreach (array_reverse(self::TABLES) as $t) DB::table($t)->delete();
                foreach (self::TABLES as $t) {
                    foreach (array_chunk($json['tables'][$t] ?? [], 200) as $chunk) DB::table($t)->insert($chunk);
                }
                // Explicit IDs from JSON do not advance PostgreSQL identity sequences.
                if (DB::getDriverName() === 'pgsql') {
                    foreach (self::TABLES as $t) {
                        if ($t === 'settings') continue;
                        DB::select("SELECT setval(pg_get_serial_sequence(?, 'id'), COALESCE(MAX(id), 1), MAX(id) IS NOT NULL) FROM \"$t\"", [$t]);
                    }
                }
            });
        } catch (\Throwable $e) {
            report($e);
            return view('utilities.restore', $data)->withErrors(['restore' => 'Restore failed and nothing was changed. Check that the backup belongs to this installation and that its referenced accounts still exist.']);
        }
        Storage::disk('local')->delete("restore-pending/{$r->token}.json");
        ActivityLog::record('Backup restored', Str::limit($data['name'] . ' · backup dated ' . $data['created']->format('M j, Y g:i A')
            . ' · replaced ' . $data['later']['Sales'] . ' later sale(s) and ' . $data['later']['Stock movements'] . ' later stock movement(s)', 250));

        return redirect()->route('utilities.index')->with('success', 'Business records restored. Please reconcile any transactions recorded after ' . $data['created']->format('M j, Y g:i A') . '.');
    }

    public function sample()
    {
        abort_if(app()->environment('production'), 403, 'Sample data is disabled in production.');
        if (Product::count() > 0) return back()->with('error', 'Sample data can only be loaded into an empty catalog.');
        $items = [
            ['Claw Hammer', 'TLS-001', 'Tools', 'piece', 295, 12, 4], ['Common Wire Nails', 'NLS-001', 'Hardware', 'kg', 85, 32, 10],
            ['Electrical Wire 2.0 mm', 'ELC-001', 'Electrical', 'meter', 22, 140, 50], ['Hollow Block 4 inch', 'BLK-004', 'Construction', 'piece', 18, 350, 100],
            ['PVC Pipe 20 mm', 'PVC-020', 'Plumbing', 'piece', 95, 6, 10], ['Portland Cement 40 kg', 'CEM-001', 'Construction', 'bag', 250, 8, 10],
            ['Steel Bar 10 mm', 'STL-010', 'Construction', 'piece', 230, 45, 15], ['White Latex Paint 4 L', 'PNT-001', 'Paint', 'can', 680, 18, 5],
        ];
        DB::transaction(function () use ($items) {
            foreach ($items as [$name, $code, $cat, $unit, $price, $qty, $reorder]) {
                $p = Product::create(['name' => $name, 'code' => $code, 'category_id' => Category::firstOrCreate(['name' => $cat])->id,
                    'base_unit' => $unit, 'selling_price' => $price, 'reorder_level' => $reorder, 'quantity_on_hand' => 0, 'is_sample' => true]);
                StockService::post($p, $qty, 'opening', ['reference' => $code, 'reason' => 'Sample opening stock']);
            }
            Supplier::create(['name' => 'Sample Supplier A', 'contact_person' => 'Sample contact', 'address' => 'Davao City', 'is_sample' => true]);
            Setting::put('sample_loaded', '1');
            ActivityLog::record('Sample data loaded', '8 products and 1 supplier');
        });
        return redirect()->route('products.index')->with('success', 'Saved successfully');
    }
}
