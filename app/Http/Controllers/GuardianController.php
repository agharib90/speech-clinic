<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Http\Requests\StoreGuardianRequest;
use App\Http\Requests\UpdateGuardianRequest;
use Illuminate\Http\Request;

class GuardianController extends Controller
{
    public function index(Request $request)
    {
        // بحث بسيط في الاسم أو الموبايل
        $search = $request->input('search');
        $guardians = Guardian::when($search, function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
        })->latest()->paginate(10);

        return view('guardians.index', compact('guardians'));
    }

    public function create()
    {
        return view('guardians.create');
    }

    public function store(StoreGuardianRequest $request)
    {
        Guardian::create($request->validated());

        return redirect()->route('guardians.index')
            ->with('success', 'تم إضافة ولي الأمر بنجاح');
    }

    public function show(Guardian $guardian)
    {
        // بنجيب المرضى (الأبناء) التابعين لولي الأمر عشان نعرضهم في صفحته
        $guardian->load('patients');
        return view('guardians.show', compact('guardian'));
    }

    public function edit(Guardian $guardian)
    {
        return view('guardians.edit', compact('guardian'));
    }

    public function update(UpdateGuardianRequest $request, Guardian $guardian)
    {
        $guardian->update($request->validated());

        return redirect()->route('guardians.index')
            ->with('success', 'تم تحديث بيانات ولي الأمر بنجاح');
    }

    public function destroy(Guardian $guardian)
    {
        $guardian->delete(); // Soft Delete

        return redirect()->route('guardians.index')
            ->with('success', 'تم حذف ولي الأمر بنجاح');
    }
}
