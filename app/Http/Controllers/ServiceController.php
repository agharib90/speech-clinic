<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::query()
            ->with('specialty')
            ->withCount('therapists')
            ->orderBy('name')
            ->paginate(20);

        return view('management.services.index', compact('services'));
    }

    public function create()
    {
        $specialties = Specialty::query()->where('is_active', true)->orderBy('name')->get();

        return view('management.services.create', compact('specialties'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        Service::create($data);

        return redirect()->route('services.index')->with('success', 'تمت إضافة الخدمة بنجاح');
    }

    public function edit(Service $service)
    {
        $specialties = Specialty::query()
            ->where(function ($query) use ($service) {
                $query->where('is_active', true)
                    ->orWhere('id', $service->specialty_id);
            })
            ->orderBy('name')
            ->get();

        return view('management.services.edit', compact('service', 'specialties'));
    }

    public function update(Request $request, Service $service)
    {
        $data = $request->validate($this->rules($service));

        if ((int) $data['specialty_id'] !== $service->specialty_id && $service->therapists()->exists()) {
            throw ValidationException::withMessages([
                'specialty_id' => 'لا يمكن نقل خدمة مرتبطة بأخصائيين إلى تخصص آخر. عطّلها وأنشئ خدمة جديدة للحفاظ على العلاقات التاريخية.',
            ]);
        }

        $service->update($data);

        return redirect()->route('services.index')->with('success', 'تم تحديث الخدمة بنجاح');
    }

    public function toggleActive(Service $service)
    {
        if (! $service->is_active && ($service->customer_price === null || (float) $service->customer_price <= 0)) {
            throw ValidationException::withMessages([
                'customer_price' => 'يجب تحديد سعر العميل قبل تفعيل الخدمة.',
            ]);
        }

        $service->update(['is_active' => ! $service->is_active]);

        return back()->with('success', $service->is_active ? 'تم تفعيل الخدمة' : 'تم تعطيل الخدمة');
    }

    private function rules(?Service $service = null): array
    {
        return [
            'specialty_id' => [
                'required',
                Rule::exists('specialties', 'id')->where(function ($query) use ($service) {
                    $query->whereNull('deleted_at')
                        ->where(function ($activeQuery) use ($service) {
                            $activeQuery->where('is_active', true);

                            if ($service) {
                                $activeQuery->orWhere('id', $service->specialty_id);
                            }
                        });
                }),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'name')
                    ->where(fn ($query) => $query->where('specialty_id', request('specialty_id')))
                    ->ignore($service),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'default_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'customer_price' => ['nullable', 'required_if:is_active,1', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
