<?php

namespace Tests\Feature\Kiosk;

use App\Models\KioskSale;
use App\Models\School;
use App\Models\User;
use App\Services\Kiosk\KioskTillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class KioskTillWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_till_close_snapshots_tender_totals_and_cash_variance_without_creating_sales(): void
    {
        $school = School::create(['name' => 'Kiosk Test School', 'code' => 'KTS', 'is_active' => true]);
        $cashier = User::factory()->create(['school_id' => $school->id]);
        $service = app(KioskTillService::class);
        $till = $service->open($school->id, $cashier->id, $cashier->id, 'Main Till', 20.00);

        KioskSale::create(['school_id' => $school->id, 'receipt_number' => 'TEST-001', 'sale_date' => now()->toDateString(), 'payment_method' => 'cash', 'subtotal' => 12.50, 'tax_amount' => 0, 'grand_total' => 12.50, 'cashier_id' => $cashier->id, 'kiosk_till_session_id' => $till->id]);
        KioskSale::create(['school_id' => $school->id, 'receipt_number' => 'TEST-002', 'sale_date' => now()->toDateString(), 'payment_method' => 'mobile_money', 'subtotal' => 5.00, 'tax_amount' => 0, 'grand_total' => 5.00, 'cashier_id' => $cashier->id, 'kiosk_till_session_id' => $till->id]);

        $closed = $service->close($till, $cashier->id, 32.00);

        $this->assertSame('closed', $closed->status);
        $this->assertSame('32.50', $closed->expected_cash);
        $this->assertSame('32.00', $closed->counted_cash);
        $this->assertSame('-0.50', $closed->cash_variance);
        $this->assertSame('12.50', $closed->cash_sales);
        $this->assertSame('5.00', $closed->mobile_money_sales);
        $this->assertSame(2, $closed->sales_count);
        $this->assertNotEmpty($closed->z_reading_number);
        $this->assertSame(2, KioskSale::where('kiosk_till_session_id', $till->id)->count(), 'Z reading must not create additional sales or receipts.');
    }

    public function test_cashier_cannot_open_a_second_concurrent_till(): void
    {
        $school = School::create(['name' => 'Kiosk Test School', 'code' => 'KTS2', 'is_active' => true]);
        $cashier = User::factory()->create(['school_id' => $school->id]);
        $service = app(KioskTillService::class);
        $service->open($school->id, $cashier->id, $cashier->id, 'Till 1', 0);

        $this->expectException(ValidationException::class);
        $service->open($school->id, $cashier->id, $cashier->id, 'Till 2', 0);
    }
}
