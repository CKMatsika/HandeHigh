<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Rules\TenantExists;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceiptController extends Controller
{
    public function __construct(private AccountingService $accountingService)
    {
    }

    public function index()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $receipts = Receipt::where('school_id', $school->id)
            ->with('customer', 'creator')
            ->orderByDesc('receipt_date')
            ->paginate(25);

        return view('admin.receipts.index', compact('receipts'));
    }

    public function create()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $customers = Customer::where('school_id', $school->id)->active()->orderBy('name')->get();
        $bankAccounts = BankAccount::where('school_id', $school->id)->active()->get();

        return view('admin.receipts.create', compact('customers', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'receipt_date' => ['required', 'date'],
            'type' => ['required', 'in:sale,service,other'],
            'customer_id' => ['nullable', TenantExists::make('customers')],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:cash,bank_transfer,check,credit_card,mobile_money'],
            'bank_account_id' => ['nullable', TenantExists::make('bank_accounts')],
            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.category' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $total = 0;
        $tax = 0;
        foreach ($validated['items'] as $item) {
            $line = $item['quantity'] * $item['unit_price'];
            $lineTax = $line * (($item['tax_rate'] ?? 0) / 100);
            $total += $line;
            $tax += $lineTax;
        }
        $grand = $total + $tax;

        $receiptNumber = 'RC-' . date('Ymd') . '-' . str_pad(Receipt::where('school_id', $school->id)->count() + 1, 5, '0', STR_PAD_LEFT);

        $receipt = Receipt::create([
            'school_id' => $school->id,
            'receipt_number' => $receiptNumber,
            'receipt_date' => $validated['receipt_date'],
            'type' => $validated['type'],
            'customer_id' => $validated['customer_id'] ?? null,
            'customer_name' => $validated['customer_name'] ?? null,
            'total_amount' => $total,
            'tax_amount' => $tax,
            'grand_total' => $grand,
            'payment_method' => $validated['payment_method'],
            'bank_account_id' => $validated['bank_account_id'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'description' => $validated['description'] ?? null,
            'notes' => null,
            'created_by' => Auth::id(),
        ]);

        foreach ($validated['items'] as $item) {
            $line = $item['quantity'] * $item['unit_price'];
            $lineTotal = $line + ($line * (($item['tax_rate'] ?? 0) / 100));
            ReceiptItem::create([
                'receipt_id' => $receipt->id,
                'description' => $item['description'],
                'category' => $item['category'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'line_total' => $lineTotal,
            ]);
        }

        $this->accountingService->postReceipt($receipt);

        return redirect()->route('admin.receipts.index')->with('success', 'Receipt recorded successfully.');
    }

    public function show(Receipt $receipt)
    {
        $this->authorizeReceipt($receipt);
        
        $receipt->load(['customer', 'items', 'creator', 'journalBatch.entries.account']);
        
        return view('admin.receipts.show', compact('receipt'));
    }

    public function edit(Receipt $receipt)
    {
        $this->authorizeReceipt($receipt);
        
        // Check if receipt can be edited (no journal entries posted)
        if ($receipt->journalBatch && $receipt->journalBatch->status === 'posted') {
            return redirect()->route('admin.receipts.show', $receipt)
                ->with('error', 'Cannot edit receipt with posted journal entries.');
        }
        
        $school = Auth::user()?->school;
        $customers = Customer::where('school_id', $school->id)->active()->orderBy('name')->get();
        $bankAccounts = BankAccount::where('school_id', $school->id)->active()->get();
        
        $receipt->load('items');
        
        return view('admin.receipts.edit', compact('receipt', 'customers', 'bankAccounts'));
    }

    public function update(Request $request, Receipt $receipt)
    {
        $this->authorizeReceipt($receipt);
        
        // Check if receipt can be edited
        if ($receipt->journalBatch && $receipt->journalBatch->status === 'posted') {
            return redirect()->route('admin.receipts.show', $receipt)
                ->with('error', 'Cannot edit receipt with posted journal entries.');
        }

        $validated = $request->validate([
            'receipt_date' => ['required', 'date'],
            'type' => ['required', 'in:sale,service,other'],
            'customer_id' => ['nullable', TenantExists::make('customers')],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:cash,bank_transfer,check,credit_card,mobile_money'],
            'bank_account_id' => ['nullable', TenantExists::make('bank_accounts')],
            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.category' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $total = 0;
        $tax = 0;
        foreach ($validated['items'] as $item) {
            $line = $item['quantity'] * $item['unit_price'];
            $lineTax = $line * (($item['tax_rate'] ?? 0) / 100);
            $total += $line;
            $tax += $lineTax;
        }
        $grand = $total + $tax;

        // Update receipt
        $receipt->update([
            'receipt_date' => $validated['receipt_date'],
            'type' => $validated['type'],
            'customer_id' => $validated['customer_id'] ?? null,
            'customer_name' => $validated['customer_name'] ?? null,
            'total_amount' => $total,
            'tax_amount' => $tax,
            'grand_total' => $grand,
            'payment_method' => $validated['payment_method'],
            'bank_account_id' => $validated['bank_account_id'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        // Delete existing items and create new ones
        $receipt->items()->delete();
        
        foreach ($validated['items'] as $item) {
            $line = $item['quantity'] * $item['unit_price'];
            $lineTotal = $line + ($line * (($item['tax_rate'] ?? 0) / 100));
            ReceiptItem::create([
                'receipt_id' => $receipt->id,
                'description' => $item['description'],
                'category' => $item['category'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'line_total' => $lineTotal,
            ]);
        }

        // Post accounting entries if not already posted
        if (!$receipt->journalBatch) {
            $this->accountingService->postReceipt($receipt);
        }

        return redirect()->route('admin.receipts.show', $receipt)
            ->with('success', 'Receipt updated successfully.');
    }

    public function destroy(Receipt $receipt)
    {
        $this->authorizeReceipt($receipt);
        
        // Check if receipt can be deleted
        if ($receipt->journalBatch && $receipt->journalBatch->status === 'posted') {
            return redirect()->route('admin.receipts.show', $receipt)
                ->with('error', 'Cannot delete receipt with posted journal entries.');
        }

        // Delete receipt items first
        $receipt->items()->delete();
        
        // Delete journal batch if exists
        if ($receipt->journalBatch) {
            $receipt->journalBatch->entries()->delete();
            $receipt->journalBatch->delete();
        }
        
        // Delete receipt
        $receipt->delete();

        return redirect()->route('admin.receipts.index')
            ->with('success', 'Receipt deleted successfully.');
    }

    public function print(Receipt $receipt)
    {
        $this->authorizeReceipt($receipt);
        
        $receipt->load(['customer', 'items', 'creator', 'school']);
        
        return view('admin.receipts.print', compact('receipt'));
    }

    public function duplicate(Receipt $receipt)
    {
        $this->authorizeReceipt($receipt);
        
        $school = Auth::user()?->school;
        $customers = Customer::where('school_id', $school->id)->active()->orderBy('name')->get();
        $bankAccounts = BankAccount::where('school_id', $school->id)->active()->get();
        
        $receipt->load('items');
        
        return view('admin.receipts.create', compact('customers', 'bankAccounts', 'receipt'));
    }

    private function authorizeReceipt(Receipt $receipt)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school || $receipt->school_id !== $school->id) {
            abort(403);
        }
    }
}
