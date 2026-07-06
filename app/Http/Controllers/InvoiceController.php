<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Quotation;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('patient')->latest()->paginate(15);
        return view('finance.invoices.index', compact('invoices'));
    }

    public function create()
    {
        $patients = Patient::where('is_active', true)->get();
        return view('finance.invoices.create', compact('patients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'issue_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // حساب الإجمالي
        $total = 0;
        foreach ($request->items as $item) {
            $total += $item['quantity'] * $item['unit_price'];
        }

        DB::transaction(function () use ($request, $total) {
            $invoice = Invoice::create([
                'patient_id' => $request->patient_id,
                'invoice_number' => 'TMP-' . Str::uuid(),
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'total' => $total,
                'status' => 'غير مدفوعة',
                'notes' => $request->notes,
            ]);

            $invoice->update([
                'invoice_number' => 'INV-' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT),
            ]);

            foreach ($request->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        });

        return redirect()->route('invoices.index')->with('success', 'تم إنشاء الفاتورة بنجاح');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('patient', 'items', 'payments');
        return view('finance.invoices.show', compact('invoice'));
    }

    // تسجيل دفعة (Partital Payment)
    public function addPayment(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'method' => 'required|string',
        ]);

        DB::transaction(function () use ($request, $invoice) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $alreadyPaid = $invoice->payments()->sum('amount');

            if (($alreadyPaid + (float) $request->amount) > (float) $invoice->total) {
                throw ValidationException::withMessages([
                    'amount' => 'قيمة الدفعة أكبر من المبلغ المتبقي في الفاتورة.',
                ]);
            }

            InvoicePayment::create([
                'invoice_id' => $invoice->id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'method' => $request->method,
                'notes' => $request->notes,
            ]);

            $totalPaid = $invoice->payments()->sum('amount');
            $invoice->update([
                'status' => $totalPaid >= $invoice->total ? 'مدفوعة' : 'مدفوعة جزئياً',
            ]);
        });

        return back()->with('success', 'تم تسجيل الدفعة بنجاح');
    }

    // طباعة الفاتورة PDF
    public function printPdf(Invoice $invoice)
    {
        $invoice->load('patient', 'items', 'payments');
        $pdf = \PDF::loadView('finance.invoices.pdf', compact('invoice'));
        return $pdf->stream('invoice-' . $invoice->invoice_number . '.pdf');
    }
}
