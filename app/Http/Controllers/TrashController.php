<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Guardian;
use App\Models\Invoice;
use Illuminate\Http\Request;

class TrashController extends Controller
{
    // عرض سلة المحذوفات
    public function index()
    {
        $trashedPatients = Patient::onlyTrashed()->with('guardian')->latest()->get();
        $trashedGuardians = Guardian::onlyTrashed()->latest()->get();
        $trashedInvoices = Invoice::onlyTrashed()->with('patient')->latest()->get();

        return view('settings.trash', compact(
            'trashedPatients', 'trashedGuardians', 'trashedInvoices'
        ));
    }

    // استعادة عنصر محذوف
    public function restore(Request $request)
    {
        $request->validate([
            'type' => 'required|in:patient,guardian,invoice',
            'id' => 'required|integer',
        ]);

        $model = null;

        switch ($request->type) {
            case 'patient':
                $model = Patient::onlyTrashed()->findOrFail($request->id);
                break;
            case 'guardian':
                $model = Guardian::onlyTrashed()->findOrFail($request->id);
                break;
            case 'invoice':
                $model = Invoice::onlyTrashed()->findOrFail($request->id);
                break;
        }

        $model->restore(); // سحر الاستعادة

        return back()->with('success', 'تم استعادة العنصر بنجاح!');
    }
}
