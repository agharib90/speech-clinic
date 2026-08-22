<?php

namespace App\Http\Controllers;

use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpecialtyController extends Controller
{
    public function index()
    {
        $specialties = Specialty::query()
            ->withCount(['services', 'therapists'])
            ->orderBy('name')
            ->paginate(20);

        return view('management.specialties.index', compact('specialties'));
    }

    public function create()
    {
        return view('management.specialties.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        Specialty::create($data);

        return redirect()->route('specialties.index')->with('success', 'تمت إضافة التخصص بنجاح');
    }

    public function edit(Specialty $specialty)
    {
        return view('management.specialties.edit', compact('specialty'));
    }

    public function update(Request $request, Specialty $specialty)
    {
        $data = $request->validate($this->rules($specialty));

        $specialty->update($data);

        return redirect()->route('specialties.index')->with('success', 'تم تحديث التخصص بنجاح');
    }

    public function toggleActive(Specialty $specialty)
    {
        $specialty->update(['is_active' => ! $specialty->is_active]);

        return back()->with('success', $specialty->is_active ? 'تم تفعيل التخصص' : 'تم تعطيل التخصص');
    }

    private function rules(?Specialty $specialty = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('specialties', 'name')->ignore($specialty),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
