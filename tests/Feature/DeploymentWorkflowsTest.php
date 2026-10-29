<?php

namespace Tests\Feature;

use App\Models\{Category, Product, Purchase, Sale, StockAdjustment, Supplier, User};
use Illuminate\Support\Facades\{Artisan, DB, Hash, Storage};
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class DeploymentWorkflowsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() === 'pgsql') {
            $database = DB::selectOne('SELECT current_database() AS name')->name;
            if (!str_ends_with($database, '_test')) {
                throw new \RuntimeException('Refusing to run destructive tests: PostgreSQL database name must end in _test.');
            }
        } elseif (config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('SQLite tests require :memory:.');
        }
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->withoutVite();
    }

    private function legacyBackupFixture(): array
    {
        $tables = ['categories', 'products', 'suppliers', 'purchases', 'purchase_items', 'deliveries', 'delivery_items',
            'sales', 'sale_items', 'stock_adjustments', 'stock_movements', 'activity_logs', 'settings'];
        $data = [];
        foreach ($tables as $table) {
            $data[$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
        }
        return ['app' => 'TukodTrack', 'version' => 1, 'created_at' => now()->toIso8601String(), 'tables' => $data];
    }

    private function owner(): User
    {
        return User::create(['name' => 'Test Owner', 'email' => 'owner@example.test',
            'password' => Hash::make('TestPassword123!'), 'role' => 'owner', 'is_active' => true]);
    }

    private function product(): Product
    {
        $this->post('/products', ['name' => 'Claw Hammer', 'code' => 'TEST-001', 'category' => 'Tools',
            'base_unit' => 'piece', 'selling_price' => 100, 'reorder_level' => 2, 'opening_stock' => 10])
            ->assertSessionHasNoErrors()->assertRedirect('/products');
        return Product::firstOrFail();
    }

    public function test_sales_receiving_and_count_approval_work(): void
    {
        $this->actingAs($this->owner());
        $p = $this->product();
        $this->post('/sales', ['items' => [['product_id' => $p->id, 'quantity' => 2]], 'amount_paid' => 250])
            ->assertSessionHasNoErrors()->assertRedirect('/sales');
        $this->assertSame(8.0, $p->fresh()->quantity_on_hand);
        $this->assertEquals(200, Sale::first()->total_amount);

        $supplier = Supplier::create(['name' => 'Test Supplier']);
        $this->post('/purchases', ['supplier_id' => $supplier->id,
            'items' => [['product_id' => $p->id, 'quantity' => 5, 'unit_cost' => 60]]])->assertRedirect('/purchases');
        $po = Purchase::firstOrFail();
        $item = $po->items()->firstOrFail();
        $this->assertSame(8.0, $p->fresh()->quantity_on_hand);
        $this->post("/purchases/{$po->id}/receive", [
            'received' => [$item->id => 3], 'verified' => '1',
            'proof_received_name' => 'Test Receiver',
        ])->assertSessionHasErrors('proof_image');
        $this->assertSame(8.0, $p->fresh()->quantity_on_hand);
        $this->assertSame('pending', $po->fresh()->status);
        $this->assertSame(0, \App\Models\Delivery::count());
        $this->post("/purchases/{$po->id}/receive", ['received' => [$item->id => 3], 'verified' => '1', 'proof_received_name' => 'Test Receiver', 'proof_image' => UploadedFile::fake()->image('delivery.jpg')])
            ->assertSessionHasNoErrors()->assertRedirect('/purchases');
        $this->assertSame('partial', $po->fresh()->status);
        $this->assertSame(11.0, $p->fresh()->quantity_on_hand);
        $this->post("/purchases/{$po->id}/receive", ['received' => [$item->id => 2], 'verified' => '1', 'proof_received_name' => 'Test Receiver', 'proof_image' => UploadedFile::fake()->image('delivery-second.jpg')])->assertRedirect('/purchases');
        $this->assertSame('received', $po->fresh()->status);
        $this->assertSame(13.0, $p->fresh()->quantity_on_hand);

        $this->post('/inventory/counts', ['product_id' => $p->id, 'counted_qty' => 12, 'reason' => 'Physical recount'])
            ->assertSessionHasNoErrors();
        $count = StockAdjustment::firstOrFail();
        $this->assertSame(13.0, $p->fresh()->quantity_on_hand);
        $this->post("/inventory/counts/{$count->id}/approve")->assertSessionHasNoErrors();
        $this->assertSame(12.0, $p->fresh()->quantity_on_hand);
    }

    public function test_failed_sale_rolls_back_and_search_ignores_case(): void
    {
        $this->actingAs($this->owner());
        $p = $this->product();
        $this->post('/sales', ['items' => [['product_id' => $p->id, 'quantity' => 100]], 'amount_paid' => 10000])
            ->assertSessionHas('error');
        $this->assertSame(0, Sale::count());
        $this->assertSame(10.0, $p->fresh()->quantity_on_hand);
        $this->get('/products?q=claw')->assertOk()->assertSee('Claw Hammer');
    }

    public function test_backup_over_200_rows_restores_and_new_ids_work(): void
    {
        $this->actingAs($owner = $this->owner());
        $this->product();
        for ($i = 0; $i < 205; $i++) Category::create(['name' => "Category $i"]);
        $backup = $this->legacyBackupFixture();
        $count = Category::count();
        Category::create(['name' => 'After backup']);
        Storage::disk('local')->put('restore-pending/'.str_repeat('a', 40).'.json', json_encode($backup));
        $this->post('/utilities/restore', ['token' => str_repeat('a', 40),
            'password' => 'TestPassword123!', 'acknowledge' => '1'])->assertRedirect('/utilities');
        $this->assertSame($count, Category::count());
        $this->assertDatabaseHas('users', ['id' => $owner->id, 'email' => $owner->email]);
        $new = Category::create(['name' => 'New after restore']);
        $this->assertGreaterThan(205, $new->id);
    }

    public function test_invalid_restore_keeps_existing_records(): void
    {
        $this->actingAs($this->owner());
        $p = $this->product();
        $backup = $this->legacyBackupFixture();
        $backup['tables']['products'][0]['category_id'] = 999999;
        Storage::disk('local')->put('restore-pending/'.str_repeat('b', 40).'.json', json_encode($backup));
        $this->post('/utilities/restore', ['token' => str_repeat('b', 40),
            'password' => 'TestPassword123!', 'acknowledge' => '1'])->assertOk()->assertSee('Restore failed and nothing was changed.');
        $this->assertSame(10.0, $p->fresh()->quantity_on_hand);
        $this->assertDatabaseHas('categories', ['name' => 'Tools']);
    }

    public function test_login_is_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'invalid@example.test', 'password' => 'wrong'])->assertRedirect();
        }
        $this->post('/login', ['email' => 'invalid@example.test', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_sample_data_is_hidden_and_blocked_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->actingAs($this->owner());
        $this->get('/overview')->assertOk()->assertDontSee('Load sample data');
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->post('/sample-data')->assertForbidden();
        $this->assertSame(0, Product::count());
    }
    public function test_business_reports_download_pdf_files(): void
    {
        $this->actingAs($this->owner());
        $this->product();
        foreach (['/reports/export?tab=summary', '/reports/export?tab=inventory',
            '/reports/export?tab=activity', '/inventory/export', '/products/export'] as $uri) {
            $response = $this->get($uri)->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-1.4', $response->getContent(), $uri);
            $this->assertStringContainsString('.pdf', $response->headers->get('content-disposition'));
        }
    }

}
