<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Patient;
use App\Models\PatientServicePlan;
use App\Models\PatientServicePlanItem;
use App\Support\PatientAccess;
use App\Support\PatientWorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('patient')->latest()->paginate(15);

        return view('finance.invoices.index', compact('invoices'));
    }

    public function create(Request $request)
    {
        $patients = Patient::where('is_active', true)->get();
        $requestedPatientId = $request->integer('patient_id');
        $selectedPatientId = $patients->contains('id', $requestedPatientId) ? $requestedPatientId : null;
        $fromWorkspace = $selectedPatientId && $request->boolean('workspace');
        $workspacePatient = $fromWorkspace ? $patients->firstWhere('id', $selectedPatientId) : null;
        $planItems = collect();

        if ($workspacePatient) {
            PatientAccess::authorize($workspacePatient, $request->user());
            $planItems = $this->invoicePlanItemsForPatient($workspacePatient->id);
        }

        return view('finance.invoices.create', compact(
            'patients',
            'selectedPatientId',
            'fromWorkspace',
            'workspacePatient',
            'planItems'
        ));
    }

    public function store(Request $request)
    {
        $fromWorkspace = PatientWorkspaceContext::validate($request, $request->integer('patient_id'));

        if ($fromWorkspace) {
            $request->merge([
                'items' => collect($request->input('items', []))
                    ->map(fn (array $item) => ['mode' => $item['mode'] ?? 'manual'] + $item)
                    ->all(),
            ]);
        }

        $rules = [
            'patient_id' => 'required|exists:patients,id',
            'issue_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.quantity' => 'required|integer|min:1',
            'workspace' => 'nullable|boolean',
        ];

        if ($fromWorkspace) {
            $rules += [
                'items.*.mode' => ['required', Rule::in(['plan', 'manual'])],
                'items.*.patient_service_plan_item_id' => ['nullable', 'required_if:items.*.mode,plan', 'integer'],
                'items.*.description' => ['nullable', 'required_if:items.*.mode,manual', 'string'],
                'items.*.unit_price' => ['nullable', 'required_if:items.*.mode,manual', 'numeric', 'min:0'],
            ];
        } else {
            $rules += [
                'items.*.description' => 'required|string',
                'items.*.unit_price' => 'required|numeric|min:0',
            ];
        }

        $data = $request->validate($rules);
        $items = $fromWorkspace
            ? $this->prepareWorkspaceInvoiceItems($data['items'], $request->integer('patient_id'))
            : collect($data['items']);

        $total = $items->sum(fn (array $item) => $item['quantity'] * $item['unit_price']);

        DB::transaction(function () use ($data, $items, $request, $total) {
            $invoice = Invoice::create([
                'patient_id' => $data['patient_id'],
                'invoice_number' => 'TMP-'.Str::uuid(),
                'issue_date' => $data['issue_date'],
                'due_date' => $request->input('due_date'),
                'total' => $total,
                'status' => 'غير مدفوعة',
                'notes' => $request->input('notes'),
            ]);

            $invoice->update([
                'invoice_number' => 'INV-'.str_pad($invoice->id, 5, '0', STR_PAD_LEFT),
            ]);

            foreach ($items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        });

        if ($fromWorkspace) {
            return redirect()->route('patients.workspace', [
                'patient' => $request->integer('patient_id'),
                'section' => 'finance',
            ])
                ->with('success', 'تم إنشاء الفاتورة بنجاح');
        }

        return redirect()->route('invoices.index')->with('success', 'تم إنشاء الفاتورة بنجاح');
    }

    private function prepareWorkspaceInvoiceItems(array $submittedItems, int $patientId)
    {
        $planItemIds = collect($submittedItems)
            ->where('mode', 'plan')
            ->pluck('patient_service_plan_item_id')
            ->filter()
            ->unique();
        $planItems = PatientServicePlanItem::query()
            ->with('service:id,name')
            ->whereIn('id', $planItemIds)
            ->whereHas('plan', fn ($query) => $query
                ->where('patient_id', $patientId)
                ->where('status', PatientServicePlan::STATUS_ACTIVE))
            ->get()
            ->keyBy('id');

        return collect($submittedItems)->map(function (array $item, int $index) use ($planItems) {
            if ($item['mode'] === 'manual') {
                return [
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ];
            }

            $planItem = $planItems->get($item['patient_service_plan_item_id']);

            if (! $planItem || ! $planItem->service || (float) $planItem->final_unit_price <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.patient_service_plan_item_id" => 'خدمة الخطة المحددة غير صالحة لهذه الحالة.',
                ]);
            }

            return [
                'description' => $planItem->service->name,
                'quantity' => $item['quantity'],
                'unit_price' => $planItem->final_unit_price,
            ];
        });
    }

    private function invoicePlanItemsForPatient(int $patientId)
    {
        return PatientServicePlanItem::query()
            ->with(['service:id,name', 'plan:id,patient_id,status'])
            ->whereHas('plan', fn ($query) => $query
                ->where('patient_id', $patientId)
                ->where('status', PatientServicePlan::STATUS_ACTIVE))
            ->where('final_unit_price', '>', 0)
            ->latest('id')
            ->get();
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('patient', 'items', 'payments');

        return view('finance.invoices.show', compact('invoice'));
    }

    // تسجيل دفعة (Partital Payment)
    public function addPayment(Request $request, Invoice $invoice)
    {
        $fromWorkspace = PatientWorkspaceContext::validate($request, (int) $invoice->patient_id);

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

        if ($fromWorkspace) {
            return redirect()->route('patients.workspace', [
                'patient' => $invoice->patient_id,
                'section' => 'finance',
            ])
                ->with('success', 'تم تسجيل الدفعة بنجاح');
        }

        return back()->with('success', 'تم تسجيل الدفعة بنجاح');
    }

    // طباعة الفاتورة PDF
    public function printPdf(Invoice $invoice)
    {
        $invoice->load('patient', 'items', 'payments');
        $pdf = \PDF::loadView('finance.invoices.pdf', compact('invoice'));

        return $pdf->stream('invoice-'.$invoice->invoice_number.'.pdf');
    }
}
