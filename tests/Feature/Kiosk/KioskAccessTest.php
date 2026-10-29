<?php

namespace Tests\Feature\Kiosk;

use App\Models\School;
use App\Models\KioskStockTake;
use App\Models\KioskProduct;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KioskAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RoleSeeder())->run();
        (new RolePermissionSeeder())->run();
    }

    public function test_cashier_can_reach_shift_dashboard_but_cannot_open_back_office(): void
    {
        $school = School::create(['name' => 'Kiosk Access School', 'code' => 'KAS', 'is_active' => true]);
        $cashier = User::factory()->create(['school_id' => $school->id]);
        $cashier->assignRole('kiosk-cashier');

        $this->actingAs($cashier)->get(route('admin.kiosk.cashier'))->assertOk();
        $this->actingAs($cashier)->get(route('admin.kiosk.index'))->assertForbidden();
    }

    public function test_cashier_role_cannot_approve_stock_takes(): void
    {
        $school = School::create(['name' => 'Kiosk Access School', 'code' => 'KAS2', 'is_active' => true]);
        $cashier = User::factory()->create(['school_id' => $school->id]);
        $cashier->assignRole('kiosk-cashier');
        $take = KioskStockTake::create(['school_id' => $school->id, 'stock_take_number' => 'KST-TEST-1', 'status' => 'pending_review', 'taken_by' => $cashier->id, 'stock_take_date' => now()->toDateString()]);

        $this->actingAs($cashier)->post(route('admin.kiosk.stock-takes.approve', $take))->assertForbidden();
    }

    public function test_stock_take_requires_an_independent_approver_before_stock_is_adjusted(): void
    {
        $school = School::create(['name' => 'Kiosk Stock School', 'code' => 'KSS', 'is_active' => true]);
        $maker = User::factory()->create(['school_id' => $school->id]);
        $checker = User::factory()->create(['school_id' => $school->id]);
        $maker->assignRole('kiosk-manager');
        $checker->assignRole('kiosk-manager');
        $product = KioskProduct::create(['school_id' => $school->id, 'code' => 'PEN-1', 'name' => 'Pen', 'category' => 'stationery', 'unit_price' => 1.00, 'stock_quantity' => 5, 'track_stock' => true, 'is_active' => true]);

        $this->actingAs($maker)->post(route('admin.kiosk.stock-takes.store'), [
            'stock_take_date' => now()->toDateString(),
            'counts' => [$product->id => 4],
        ])->assertRedirect(route('admin.kiosk.stock-takes.index'));

        $take = KioskStockTake::where('school_id', $school->id)->firstOrFail();
        $this->assertSame(5, $product->fresh()->stock_quantity, 'Submission must not alter inventory.');

        $this->actingAs($maker)->post(route('admin.kiosk.stock-takes.approve', $take))->assertSessionHasErrors('stock_take');
        $this->assertSame(5, $product->fresh()->stock_quantity, 'The maker cannot approve their own count.');

        $this->actingAs($checker)->post(route('admin.kiosk.stock-takes.approve', $take))->assertRedirect(route('admin.kiosk.stock-takes.index'));
        $this->assertSame(4, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('kiosk_stock_movements', ['kiosk_product_id' => $product->id, 'movement_type' => 'stock_take_adjustment', 'quantity_delta' => -1]);
    }
}
