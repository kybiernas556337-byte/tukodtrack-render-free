<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RevisionWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role = 'owner'): User
    {
        return User::create(['name' => 'Tester', 'email' => uniqid().'@example.test', 'role' => $role, 'is_active' => true, 'password' => Hash::make('OriginalPass123!')]);
    }

    public function test_void_restores_stock_only_once_and_requires_owner(): void
    {
        $owner = $this->account();
        $this->actingAs($owner);
        $p = Product::create(['code' => 'P1', 'name' => 'Hammer', 'selling_price' => 100, 'quantity_on_hand' => 10]);
        $this->post('/sales', ['items' => [['product_id' => $p->id, 'quantity' => 2]], 'amount_paid' => 200])->assertSessionHasNoErrors();
        $sale = Sale::firstOrFail();
        $this->actingAs($this->account('cashier'))->post('/sales/'.$sale->id.'/void', ['void_reason' => 'Mistake'])->assertForbidden();
        $this->actingAs($owner)->post('/sales/'.$sale->id.'/void', ['void_reason' => 'Mistake'])->assertSessionHasNoErrors();
        $this->assertEquals(10, $p->fresh()->quantity_on_hand);
        $this->post('/sales/'.$sale->id.'/void', ['void_reason' => 'Again'])->assertSessionHasErrors('void_reason');
        $this->assertEquals(10, $p->fresh()->quantity_on_hand);
        $this->assertEquals(1, StockMovement::where('movement_type', 'reversal')->count());
    }

    public function test_custom_roles_follow_their_profile_without_owner_access(): void
    {
        $this->actingAs($this->account())->post('/roles', ['name' => 'Warehouse Assistant', 'access_profile' => 'stock'])->assertSessionHasNoErrors();
        $role = Role::where('name', 'Warehouse Assistant')->firstOrFail();
        $this->actingAs($this->account($role->key));
        $this->get('/inventory/report')->assertOk();
        $this->get('/sales')->assertForbidden();
        $this->get('/user-access')->assertForbidden();
        $this->get('/utilities/backup')->assertForbidden();
        $this->get('/overview')->assertOk()->assertSee('Inventory');
    }

    public function test_password_change_requires_current_password_and_confirmation(): void
    {
        $user = $this->account();
        $user->update(['must_change_password' => true]);
        $this->actingAs($user);
        $this->get('/sales')->assertRedirect('/password');
        $this->post('/password', ['current_password' => 'incorrect', 'password' => 'Replacement123!', 'password_confirmation' => 'Replacement123!'])->assertSessionHasErrors('current_password');
        $this->post('/password', ['current_password' => 'OriginalPass123!', 'password' => 'Replacement123!', 'password_confirmation' => 'Replacement123!'])->assertRedirect('/overview');
        $this->assertTrue(Hash::check('Replacement123!', $user->fresh()->password));
        $this->assertFalse((bool) $user->fresh()->must_change_password);
    }

    public function test_invalid_profile_and_inactive_user_are_rejected(): void
    {
        $this->actingAs($owner = $this->account())->post('/roles', ['name' => 'Unsafe', 'access_profile' => 'owner'])->assertSessionHasErrors('access_profile');
        $owner->update(['is_active' => false]);
        $this->get('/overview')->assertForbidden();
    }

    public function test_sql_backup_fails_clearly_on_non_postgres(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('SQLite-specific guard');
        }
        $this->actingAs($this->account())->get('/utilities/backup')->assertRedirect()->assertSessionHas('error');
    }
}
