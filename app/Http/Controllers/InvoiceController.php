<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices with filters
     */
    public function index(Request $request): View
    {
        $query = Invoice::with(['customer', 'createdBy']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('issue_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('issue_date', '<=', $request->date_to);
        }

        // Search by invoice number
        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', '%' . $request->search . '%');
        }

        $invoices = $query->latest()->paginate(20);
        $customers = Customer::orderBy('name')->get();

        return view('invoices.index', compact('invoices', 'customers'));
    }

    /**
     * Show the form for creating a new invoice
     */
    public function create(Request $request): View
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        
        // Check if creating from a sale
        $sale = null;
        if ($request->filled('sale_id')) {
            $sale = Sale::with(['items.product', 'customer'])->findOrFail($request->sale_id);
        }

        return view('invoices.create', compact('customers', 'products', 'sale'));
    }

    /**
     * Store a newly created invoice
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_id' => 'nullable|exists:sales,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'payment_terms' => 'nullable|string|max:255',
            'tax_amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_per_item' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Generate invoice number
            $invoiceNumber = Invoice::generateInvoiceNumber();

            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $lineTotal = ($item['quantity'] * $item['unit_price']) - ($item['discount_per_item'] ?? 0);
                $subtotal += $lineTotal;
            }

            $total = $subtotal + $validated['tax_amount'] - ($validated['discount_amount'] ?? 0);

            // Create invoice
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $validated['customer_id'],
                'sale_id' => $validated['sale_id'] ?? null,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax_amount' => $validated['tax_amount'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'total_amount' => $total,
                'amount_paid' => 0,
                'balance_due' => $total,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create invoice items
            foreach ($validated['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_per_item' => $item['discount_per_item'] ?? 0,
                ]);
            }

            DB::commit();

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Error creating invoice: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified invoice
     */
    public function show(Invoice $invoice): View
    {
        $invoice->load(['customer', 'items.product', 'payments.recordedBy', 'createdBy', 'sale']);
        
        // Check if invoice is overdue and update status
        $invoice->checkAndUpdateOverdueStatus();

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified invoice
     */
    public function edit(Invoice $invoice): View|RedirectResponse
    {
        if (!$invoice->canEdit()) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $customers = Customer::orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $invoice->load('items.product');

        return view('invoices.edit', compact('invoice', 'customers', 'products'));
    }

    /**
     * Update the specified invoice
     */
    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        if (!$invoice->canEdit()) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'payment_terms' => 'nullable|string|max:255',
            'tax_amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_per_item' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $lineTotal = ($item['quantity'] * $item['unit_price']) - ($item['discount_per_item'] ?? 0);
                $subtotal += $lineTotal;
            }

            $total = $subtotal + $validated['tax_amount'] - ($validated['discount_amount'] ?? 0);

            // Update invoice
            $invoice->update([
                'customer_id' => $validated['customer_id'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'subtotal' => $subtotal,
                'tax_amount' => $validated['tax_amount'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'total_amount' => $total,
                'balance_due' => $total - $invoice->amount_paid,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Delete existing items and create new ones
            $invoice->items()->delete();
            foreach ($validated['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_per_item' => $item['discount_per_item'] ?? 0,
                ]);
            }

            DB::commit();

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Error updating invoice: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified invoice
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        if (!$invoice->canDelete()) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only draft or cancelled invoices can be deleted.');
        }

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully!');
    }

    /**
     * Display printable invoice
     */
    public function print(Invoice $invoice): View
    {
        $invoice->load(['customer', 'items.product', 'createdBy']);
        return view('invoices.print', compact('invoice'));
    }

    /**
     * Mark invoice as sent
     */
    public function markAsSent(Invoice $invoice): RedirectResponse
    {
        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be marked as sent.');
        }

        $invoice->markAsSent();

        return back()->with('success', 'Invoice marked as sent!');
    }

    /**
     * Record a payment for the invoice
     */
    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->balance_due <= 0) {
            return back()->with('error', 'This invoice is already fully paid.');
        }

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->balance_due,
            'payment_method' => 'required|in:cash,mpesa,card,bank_transfer,check',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            $invoice->recordPayment(
                $validated['amount'],
                $validated['payment_method'],
                $validated['reference_number'] ?? null,
                $validated['notes'] ?? null
            );

            return back()->with('success', 'Payment recorded successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }

    /**
     * Create invoice from existing sale
     */
    public function createFromSale(Sale $sale): RedirectResponse
    {
        // Redirect to create form with sale_id parameter
        return redirect()->route('invoices.create', ['sale_id' => $sale->id]);
    }

    /**
     * Cancel an invoice
     */
    public function cancel(Invoice $invoice): RedirectResponse
    {
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return back()->with('error', 'Cannot cancel a paid or already cancelled invoice.');
        }

        $invoice->cancel();

        return back()->with('success', 'Invoice cancelled successfully!');
    }
}
