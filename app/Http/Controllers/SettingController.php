<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    use AuthorizesRequests;

    private const TEMPLATE_VARIABLES = [
        'patient_name',
        'guardian_name',
        'appointment_date',
        'appointment_time',
        'clinic_name',
        'invoice_number',
        'invoice_total',
        'invoice_due',
    ];

    public function edit()
    {
        $this->ensureSettingsAccess();

        $settings = $this->settings();
        $workingHours = $settings->working_hours ?: Setting::defaultWorkingHours();
        $templateVariables = self::TEMPLATE_VARIABLES;

        return view('settings.index', compact('settings', 'workingHours', 'templateVariables'));
    }

    public function update(Request $request)
    {
        $this->ensureSettingsAccess();

        $section = $request->validate([
            'section' => ['required', 'in:clinic,financial,working_hours,notifications,system'],
        ])['section'];

        $settings = $this->settings();

        match ($section) {
            'clinic' => $this->updateClinicSettings($request, $settings),
            'financial' => $this->updateFinancialSettings($request, $settings),
            'working_hours' => $this->updateWorkingHours($request, $settings),
            'notifications' => $this->updateNotificationSettings($request, $settings),
            'system' => $this->updateSystemSettings($request, $settings),
        };

        return back()->with('success', 'تم حفظ إعدادات القسم بنجاح');
    }

    private function settings(): Setting
    {
        return Setting::firstOrCreate([], Setting::defaults());
    }

    private function ensureSettingsAccess(): void
    {
        $user = auth()->user();

        abort_unless($user?->can('manage settings') || $user?->hasRole('مدير النظام'), 403);
    }

    private function updateClinicSettings(Request $request, Setting $settings): void
    {
        $data = $request->validate([
            'clinic_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'official_phone' => ['nullable', 'string', 'max:50'],
            'official_email' => ['nullable', 'email', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'commercial_registration' => ['nullable', 'string', 'max:100'],
            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:1024',
                'dimensions:min_width=120,min_height=60,max_width=2000,max_height=1000',
            ],
        ]);

        unset($data['logo']);

        $oldLogoPath = $settings->logo_path;
        $newLogoPath = null;

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $extension = $file->getClientOriginalExtension();
            $storedName = (string) Str::uuid().($extension ? ".{$extension}" : '');
            $newLogoPath = $file->storeAs('settings/logos', $storedName, 'public');

            $data['logo_path'] = $newLogoPath;
            $data['logo_original_name'] = $file->getClientOriginalName();
        }

        $settings->update($data);

        if ($newLogoPath && $oldLogoPath && $oldLogoPath !== $newLogoPath) {
            Storage::disk('public')->delete($oldLogoPath);
        }
    }

    private function updateFinancialSettings(Request $request, Setting $settings): void
    {
        $data = $request->validate([
            'currency_code' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'default_therapist_commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'appointment_confirmation_deposit_percentage' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $data['currency'] = $data['currency_symbol'];

        $settings->update($data);
    }

    private function updateWorkingHours(Request $request, Setting $settings): void
    {
        $request->validate([
            'working_hours' => ['required', 'array'],
            'working_hours.*.is_open' => ['nullable', 'boolean'],
            'working_hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'working_hours.*.closes_at' => ['nullable', 'date_format:H:i'],
        ]);

        $workingHours = [];

        foreach (Setting::defaultWorkingHours() as $dayKey => $defaultDay) {
            $day = $request->input("working_hours.{$dayKey}", []);
            $isOpen = (bool) ($day['is_open'] ?? false);
            $opensAt = $day['opens_at'] ?? null;
            $closesAt = $day['closes_at'] ?? null;

            if ($isOpen) {
                if (! $opensAt || ! $closesAt) {
                    throw ValidationException::withMessages([
                        "working_hours.{$dayKey}.opens_at" => 'وقت البداية والنهاية مطلوبان عند تفعيل يوم العمل.',
                    ]);
                }

                if ($closesAt <= $opensAt) {
                    throw ValidationException::withMessages([
                        "working_hours.{$dayKey}.closes_at" => 'وقت الإغلاق يجب أن يكون بعد وقت البداية.',
                    ]);
                }
            } else {
                $opensAt = null;
                $closesAt = null;
            }

            $workingHours[$dayKey] = [
                'label' => $defaultDay['label'],
                'is_open' => $isOpen,
                'opens_at' => $opensAt,
                'closes_at' => $closesAt,
            ];
        }

        $settings->update(['working_hours' => $workingHours]);
    }

    private function updateNotificationSettings(Request $request, Setting $settings): void
    {
        $data = $request->validate([
            'appointment_reminders_enabled' => ['required', 'boolean'],
            'appointment_reminder_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'invoice_notifications_enabled' => ['required', 'boolean'],
            'notification_sender_number' => ['nullable', 'string', 'max:50'],
            'notification_sender_name' => ['nullable', 'string', 'max:100'],
            'appointment_reminder_template' => ['required', 'string', 'max:1000'],
            'invoice_notification_template' => ['required', 'string', 'max:1000'],
        ]);

        $this->validateTemplate('appointment_reminder_template', $data['appointment_reminder_template']);
        $this->validateTemplate('invoice_notification_template', $data['invoice_notification_template']);

        $settings->update($data);
    }

    private function updateSystemSettings(Request $request, Setting $settings): void
    {
        $data = $request->validate([
            'default_locale' => ['required', 'in:ar'],
            'trash_retention_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $settings->update($data);
    }

    private function validateTemplate(string $field, string $template): void
    {
        if ($template !== strip_tags($template)) {
            throw ValidationException::withMessages([
                $field => 'قالب الرسالة يجب أن يكون نصًا عاديًا بدون HTML.',
            ]);
        }

        preg_match_all('/{([a-zA-Z0-9_]+)}/', $template, $matches);

        $unknown = collect($matches[1] ?? [])
            ->unique()
            ->reject(fn ($variable) => in_array($variable, self::TEMPLATE_VARIABLES, true))
            ->values();

        if ($unknown->isNotEmpty()) {
            throw ValidationException::withMessages([
                $field => 'يحتوي القالب على متغير غير مسموح: '.$unknown->implode(', '),
            ]);
        }
    }
}
