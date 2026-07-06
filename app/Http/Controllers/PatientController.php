<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Guardian;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $patients = Patient::with('guardian')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhereHas('guardian', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })->latest()->paginate(10);

        return view('patients.index', compact('patients'));
    }

    public function create()
    {
        // جلب أولياء الأمور لاختيار أحدهم عند إنشاء المريض
        $guardians = Guardian::pluck('name', 'id');
        return view('patients.create', compact('guardians'));
    }

    public function store(StorePatientRequest $request)
    {
        $data = $request->validated();

        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        DB::transaction(function () use ($data) {
            $data['barcode'] = 'TMP-' . Str::uuid();
            $data['qr_code'] = $data['barcode'];

            $patient = Patient::create($data);
            $barcode = 'PAT-' . str_pad($patient->id, 5, '0', STR_PAD_LEFT);

            $patient->update([
                'barcode' => $barcode,
                'qr_code' => $barcode,
            ]);
        });

        return redirect()->route('patients.index')
            ->with('success', 'تم تسجيل الطفل وتوليد الباركود بنجاح');
    }

    public function show(Patient $patient)
    {
        $patient->load('guardian');
        return view('patients.show', compact('patient'));
    }

    public function edit(Patient $patient)
    {
        $guardians = Guardian::pluck('name', 'id');
        return view('patients.edit', compact('patient', 'guardians'));
    }

    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $data = $request->validated();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        $patient->update($data);

        return redirect()->route('patients.index')
            ->with('success', 'تم تحديث بيانات الطفل بنجاح');
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();
        return redirect()->route('patients.index')
            ->with('success', 'تم حذف الطفل بنجاح');
    }

    // دالة طباعة البطاقة
    public function printCard(Patient $patient)
    {
        $patient->load('guardian');
        return view('patients.print-card', compact('patient'));
    }
}
