<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Cashbook;
use App\Models\Department;
use App\Models\FeeStructure;
use App\Models\InvoiceItem;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\KioskSale;
use App\Models\Project;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncProjectsAndCostCenters extends Command
{
    protected $signature = 'finance:sync-projects-cost-centers 
                            {--school= : Specific school ID to sync} 
                            {--dry-run : Run in preview mode without committing updates}';

    protected $description = 'Safely synchronize historical and unallocated financial transactions (kiosk sales, bus fees, vehicle bills) with Projects and Cost Centers for Profit and Loss statements';

    public function handle(): int
    {
        $schoolId = $this->option('school');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE: No database changes will be committed.');
        }

        $schools = $schoolId ? School::where('id', $schoolId)->get() : School::all();

        if ($schools->isEmpty()) {
            $this->error('No matching school found.');
            return 1;
        }

        foreach ($schools as $school) {
            $this->info("Processing School: {$school->name} (ID: {$school->id})");
            $this->syncSchool($school, $dryRun);
        }

        $this->info('Project & Cost Center synchronization completed successfully.');
        return 0;
    }

    protected function syncSchool(School $school, bool $dryRun): void
    {
        $stats = [
            'kiosk_sales' => 0,
            'fee_structures' => 0,
            'invoice_items' => 0,
            'bills' => 0,
            'bill_items' => 0,
            'receipts' => 0,
            'journal_entries' => 0,
        ];

        // 1. Resolve or create standard commercial projects and departments
        $busProject = Project::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Bus Operations'],
            [
                'code' => 'BUS-OPS',
                'description' => 'School bus transport, charter, and maintenance operations',
                'type' => 'operational',
                'status' => 'in_progress',
                'start_date' => now()->startOfYear()->toDateString(),
            ]
        );

        $transportDept = Department::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Transport'],
            ['description' => 'Transport and fleet management department']
        );

        $kioskProject = Project::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Tuckshop / Kiosk'],
            [
                'code' => 'KIOSK-OPS',
                'description' => 'School tuckshop and kiosk commercial operations',
                'type' => 'commercial',
                'status' => 'in_progress',
                'start_date' => now()->startOfYear()->toDateString(),
            ]
        );

        $canteenDept = Department::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Canteen'],
            ['description' => 'Canteen and tuckshop catering operations']
        );

        $boardingProject = Project::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Boarding Operations'],
            [
                'code' => 'BRD-OPS',
                'description' => 'Hostel and boarding student welfare services',
                'type' => 'operational',
                'status' => 'in_progress',
                'start_date' => now()->startOfYear()->toDateString(),
            ]
        );

        $boardingDept = Department::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Boarding'],
            ['description' => 'Hostels and student boarding department']
        );

        // 2. Synchronize Kiosk Sales
        $untaggedKiosks = KioskSale::where('school_id', $school->id)
            ->where(function ($q) {
                $q->whereNull('project_id')->orWhereNull('cost_center_id');
            })->get();

        foreach ($untaggedKiosks as $sale) {
            $stats['kiosk_sales']++;
            if (! $dryRun) {
                $sale->update([
                    'project_id' => $kioskProject->id,
                    'cost_center_id' => $canteenDept->id,
                ]);

                // Sync associated cashbook entry
                Cashbook::where('school_id', $school->id)
                    ->where('source_type', 'kiosk')
                    ->where('source_id', $sale->id)
                    ->update([
                        'project_id' => $kioskProject->id,
                        'cost_center_id' => $canteenDept->id,
                    ]);

                // Sync journal entries for this kiosk batch
                if ($sale->journal_batch_id) {
                    JournalEntry::where('journal_batch_id', $sale->journal_batch_id)
                        ->update([
                            'project_id' => $kioskProject->id,
                            'cost_center_id' => $canteenDept->id,
                        ]);
                    $stats['journal_entries']++;
                }
            }
        }

        // 3. Synchronize Fee Structures (Transport & Boarding)
        $transportFees = FeeStructure::where('school_id', $school->id)
            ->where(function ($q) {
                $q->where('category', 'transport')
                    ->orWhere('service_type', 'transport')
                    ->orWhere('label', 'like', '%transport%')
                    ->orWhere('label', 'like', '%bus%');
            })
            ->where(function ($q) {
                $q->whereNull('project_id')->orWhereNull('cost_center_id');
            })->get();

        foreach ($transportFees as $fee) {
            $stats['fee_structures']++;
            if (! $dryRun) {
                $fee->update([
                    'project_id' => $busProject->id,
                    'cost_center_id' => $transportDept->id,
                ]);
            }
        }

        $boardingFees = FeeStructure::where('school_id', $school->id)
            ->where(function ($q) {
                $q->where('category', 'boarding')
                    ->orWhere('service_type', 'boarding')
                    ->orWhere('label', 'like', '%boarding%')
                    ->orWhere('label', 'like', '%hostel%');
            })
            ->where(function ($q) {
                $q->whereNull('project_id')->orWhereNull('cost_center_id');
            })->get();

        foreach ($boardingFees as $fee) {
            $stats['fee_structures']++;
            if (! $dryRun) {
                $fee->update([
                    'project_id' => $boardingProject->id,
                    'cost_center_id' => $boardingDept->id,
                ]);
            }
        }

        // 4. Synchronize Invoice Items & their journal entries
        // A. Transport invoice items
        $transportInvoiceItems = InvoiceItem::where('school_id', $school->id)
            ->where(function ($q) {
                $q->where('category', 'transport')
                    ->orWhere('description', 'like', '%transport%')
                    ->orWhere('description', 'like', '%bus%');
            })
            ->where(function ($q) {
                $q->whereNull('project_id')->orWhereNull('cost_center_id');
            })->get();

        foreach ($transportInvoiceItems as $item) {
            $stats['invoice_items']++;
            if (! $dryRun) {
                $item->update([
                    'project_id' => $busProject->id,
                    'cost_center_id' => $transportDept->id,
                ]);
            }
        }

        // B. Boarding invoice items
        $boardingInvoiceItems = InvoiceItem::where('school_id', $school->id)
            ->where(function ($q) {
                $q->where('category', 'boarding')
                    ->orWhere('description', 'like', '%boarding%')
                    ->orWhere('description', 'like', '%hostel%');
            })
            ->where(function ($q) {
                $q->whereNull('project_id')->orWhereNull('cost_center_id');
            })->get();

        foreach ($boardingInvoiceItems as $item) {
            $stats['invoice_items']++;
            if (! $dryRun) {
                $item->update([
                    'project_id' => $boardingProject->id,
                    'cost_center_id' => $boardingDept->id,
                ]);
            }
        }

        // 5. Synchronize Bills (Fuel, Vehicle Maintenance, Bus Parts, Canteen Provisions)
        $busBills = Bill::where('school_id', $school->id)
            ->where(function ($q) {
                $q->where('title', 'like', '%fuel%')
                    ->orWhere('title', 'like', '%diesel%')
                    ->orWhere('title', 'like', '%petrol%')
                    ->orWhere('title', 'like', '%bus%')
                    ->orWhere('title', 'like', '%vehicle%')
                    ->orWhere('title', 'like', '%tyre%')
                    ->orWhere('title', 'like', '%transport%')
                    ->orWhereHas('vendor', function ($vq) {
                        $vq->where('name', 'like', '%fuel%')
                            ->orWhere('name', 'like', '%garage%')
                            ->orWhere('name', 'like', '%motors%')
                            ->orWhere('name', 'like', '%petroleum%')
                            ->orWhere('name', 'like', '%transport%');
                    });
            })
            ->where(function ($q) {
                $q->whereNull('project_id')->orWhereNull('cost_center_id');
            })->get();

        foreach ($busBills as $bill) {
            $stats['bills']++;
            if (! $dryRun) {
                $bill->update([
                    'project_id' => $busProject->id,
                    'cost_center_id' => $transportDept->id,
                ]);

                BillItem::where('bill_id', $bill->id)->update([
                    'project_id' => $busProject->id,
                    'cost_center_id' => $transportDept->id,
                ]);
                $stats['bill_items']++;

                // Propagate to posted bill journal entries
                $batch = JournalBatch::where('school_id', $school->id)
                    ->where('source_type', 'bill')
                    ->where('source_id', $bill->id)
                    ->first();

                if ($batch) {
                    JournalEntry::where('journal_batch_id', $batch->id)
                        ->where(function ($q) {
                            $q->whereNull('project_id')->orWhereNull('cost_center_id');
                        })
                        ->update([
                            'project_id' => $busProject->id,
                            'cost_center_id' => $transportDept->id,
                        ]);
                    $stats['journal_entries']++;
                }
            }
        }

        // 6. Synchronize Tuckshop / Canteen Bills
        $canteenBills = Bill::where('school_id', $school->id)
            ->where(function ($q) {
                $q->where('title', 'like', '%tuckshop%')
                    ->orWhere('title', 'like', '%kiosk%')
                    ->orWhere('title', 'like', '%canteen%')
                    ->orWhere('title', 'like', '%bread%')
                    ->orWhere('title', 'like', '%beverage%')
                    ->orWhere('title', 'like', '%drinks%')
                    ->orWhere('title', 'like', '%groceries%')
                    ->orWhereHas('vendor', function ($vq) {
                        $vq->where('name', 'like', '%bakery%')
                            ->orWhere('name', 'like', '%canteen%')
                            ->orWhere('name', 'like', '%tuckshop%')
                            ->orWhere('name', 'like', '%beverages%');
                    });
            })
            ->where(function ($q) {
                $q->whereNull('project_id')->orWhereNull('cost_center_id');
            })->get();

        foreach ($canteenBills as $bill) {
            $stats['bills']++;
            if (! $dryRun) {
                $bill->update([
                    'project_id' => $kioskProject->id,
                    'cost_center_id' => $canteenDept->id,
                ]);

                BillItem::where('bill_id', $bill->id)->update([
                    'project_id' => $kioskProject->id,
                    'cost_center_id' => $canteenDept->id,
                ]);
                $stats['bill_items']++;

                $batch = JournalBatch::where('school_id', $school->id)
                    ->where('source_type', 'bill')
                    ->where('source_id', $bill->id)
                    ->first();

                if ($batch) {
                    JournalEntry::where('journal_batch_id', $batch->id)
                        ->where(function ($q) {
                            $q->whereNull('project_id')->orWhereNull('cost_center_id');
                        })
                        ->update([
                            'project_id' => $kioskProject->id,
                            'cost_center_id' => $canteenDept->id,
                        ]);
                    $stats['journal_entries']++;
                }
            }
        }

        // Output summary table
        $this->table(
            ['Entity', $dryRun ? 'Items Detected (Would Sync)' : 'Items Successfully Synced'],
            [
                ['Kiosk Sales', $stats['kiosk_sales']],
                ['Fee Structures (Transport/Boarding)', $stats['fee_structures']],
                ['Invoice Items (Transport/Boarding)', $stats['invoice_items']],
                ['Vendor Bills', $stats['bills']],
                ['Bill Line Items', $stats['bill_items']],
                ['Journal Entries Updated', $stats['journal_entries']],
            ]
        );
    }
}
