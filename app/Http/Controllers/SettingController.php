<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        // أول مرة نفتحها لو مفيش إعدادات نعمل سطر افتراضي
        $settings = Setting::firstOrCreate(
            ['id' => 1],
            ['clinic_name' => 'عيادة التخاطب', 'currency' => 'ر.س']
        );

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'clinic_name' => 'required|string',
            'currency' => 'required|string|max:10',
        ]);

        $settings = Setting::firstOrCreate(['id' => 1]);
        $settings->update($request->all());

        return back()->with('success', 'تم حفظ الإعدادات بنجاح');
    }
}
