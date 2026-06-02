<?php

namespace App\Http\Controllers;

use App\Models\SessionPackage;
use App\Models\Patient;
use Illuminate\Http\Request;

class SessionPackageController extends Controller
{
    public function index()
    {
        $packages = SessionPackage::with('patient')->latest()->paginate(15);
        return view('finance.packages.index', compact('packages'));
    }

    public function create()
    {
        $patients = Patient::where('is_active', true)->get();
        return view('finance.packages.create', compact('patients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'name' => 'required|string',
            'total_sessions' => 'required|integer|min:1',
            'price_paid' => 'required|numeric|min:0',
        ]);

        SessionPackage::create([
            'patient_id' => $request->patient_id,
            'name' => $request->name,
            'total_sessions' => $request->total_sessions,
            'used_sessions' => 0,
            'price_paid' => $request->price_paid,
            'start_date' => now(),
            'status' => 'نشط',
        ]);

        return redirect()->route('packages.index')->with('success', 'تم بيع الباقة بنجاح! سيتم الخصم تلقائياً عند إكمال الجلسات.');
    }
}
