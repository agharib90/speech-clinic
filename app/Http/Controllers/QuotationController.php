<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function index()
    {
        $quotations = Quotation::with('patient')->latest()->paginate(15);
        return view('finance.quotations.index', compact('quotations'));
    }

    public function create()
    {
        $patients = Patient::where('is_active', true)->get();
        return view('finance.quotations.create', compact('patients'));
    }

    public function store(Request $request)
    {
        // نفس منطق الفاتورة بالظبط بس لـ Quotation
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'issue_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $total = 0;
        foreach ($request->items as $item) {
            $total += $item['quantity'] * $item['unit_price'];
        }

        $quotation = Quotation::create([
            'patient_id' => $request->patient_id,
            'quotation_number' => 'QUO-' . str_pad(Quotation::max('id') + 1, 5, '0', STR_PAD_LEFT),
            'issue_date' => $request->issue_date,
            'valid_until' => $request->valid_until,
            'total' => $total,
            'status' => 'مسودة',
            'notes' => $request->notes,
        ]);

        foreach ($request->items as $item) {
            QuotationItem::create([
                'quotation_id' => $quotation->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        return redirect()->route('quotations.index')->with('success', 'تم إنشاء عرض السعر بنجاح');
    }

    // تحويل عرض السعر لفاتورة بنقرة واحدة
    public function convertToInvoice(Quotation $quotation)
    {
        $quotation->load('items');

        // إنشاء الفاتورة
        $invoice = Invoice::create([
            'patient_id' => $quotation->patient_id,
            'quotation_id' => $quotation->id,
            'invoice_number' => 'INV-' . str_pad(Invoice::max('id') + 1, 5, '0', STR_PAD_LEFT),
            'issue_date' => now()->format('Y-m-d'),
            'total' => $quotation->total,
            'status' => 'غير مدفوعة',
            'notes' => $quotation->notes,
        ]);

        // نسخ العناصر من العرض للفاتورة
        foreach ($quotation->items as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
            ]);
        }

        // تحديث حالة عرض السعر
        $quotation->update(['status' => 'مقبول']);

        return redirect()->route('invoices.show', $invoice)->with('success', 'تم تحويل عرض السعر لفاتورة بنجاح!');
    }
}
