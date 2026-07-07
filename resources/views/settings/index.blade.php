<x-app-layout>
    <x-slot name="title">إعدادات النظام</x-slot>

    @php
        $fieldClass = 'w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
        $labelClass = 'mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300';
        $hintClass = 'mt-1 text-xs text-gray-500 dark:text-gray-400';
        $defaultSettings = \App\Models\Setting::defaults();
        $sectionLinks = [
            '#clinic' => 'بيانات العيادة',
            '#financial' => 'الإعدادات المالية',
            '#hours' => 'ساعات العمل',
            '#notifications' => 'الإشعارات والتواصل',
            '#system' => 'إعدادات النظام',
        ];
    @endphp

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                يرجى مراجعة الحقول المحددة قبل الحفظ.
            </div>
        @endif

        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">إعدادات النظام</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">إعدادات تشغيلية آمنة للعيادة دون تخزين أسرار أو تغيير بيانات مالية تاريخية.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
            <aside class="h-fit rounded-lg border border-gray-100 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <nav class="space-y-1">
                    @foreach($sectionLinks as $href => $label)
                        <a href="{{ $href }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                            {{ $label }}
                        </a>
                    @endforeach
                    @role('مدير النظام')
                        <a href="#backup" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                            إدارة البيانات والنسخ الاحتياطي
                        </a>
                    @endrole
                </nav>
            </aside>

            <div class="space-y-6">
                <section id="clinic" class="rounded-lg border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                        <h2 class="font-bold text-gray-900 dark:text-white">بيانات العيادة</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">تظهر هذه البيانات في الواجهة والتقارير الرسمية عند دعمها.</p>
                    </div>
                    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="p-5">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="clinic">

                        <div class="grid gap-5 md:grid-cols-2">
                            <label class="block md:col-span-2">
                                <span class="{{ $labelClass }}">اسم العيادة / المركز</span>
                                <input type="text" name="clinic_name" value="{{ old('clinic_name', $settings->clinic_name) }}" class="{{ $fieldClass }}" required>
                                @error('clinic_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="block md:col-span-2">
                                <span class="{{ $labelClass }}">العنوان</span>
                                <input type="text" name="address" value="{{ old('address', $settings->address) }}" class="{{ $fieldClass }}">
                                @error('address') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="{{ $labelClass }}">رقم الهاتف الرسمي</span>
                                <input type="text" name="official_phone" value="{{ old('official_phone', $settings->official_phone) }}" class="{{ $fieldClass }}">
                                @error('official_phone') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="{{ $labelClass }}">البريد الإلكتروني الرسمي</span>
                                <input type="email" name="official_email" value="{{ old('official_email', $settings->official_email) }}" class="{{ $fieldClass }}">
                                @error('official_email') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="{{ $labelClass }}">الرقم الضريبي</span>
                                <input type="text" name="tax_number" value="{{ old('tax_number', $settings->tax_number) }}" class="{{ $fieldClass }}">
                                @error('tax_number') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="{{ $labelClass }}">السجل التجاري</span>
                                <input type="text" name="commercial_registration" value="{{ old('commercial_registration', $settings->commercial_registration) }}" class="{{ $fieldClass }}">
                                @error('commercial_registration') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <div class="md:col-span-2">
                                <span class="{{ $labelClass }}">شعار العيادة</span>
                                <div class="grid gap-4 rounded-lg border border-gray-100 p-4 dark:border-gray-700 md:grid-cols-[160px_1fr] md:items-center">
                                    <div class="flex h-28 items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-700">
                                        @if($settings->logo_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($settings->logo_path) }}" alt="شعار العيادة" class="max-h-24 max-w-32 object-contain">
                                        @else
                                            <span class="text-sm text-gray-400 dark:text-gray-500">لا يوجد شعار</span>
                                        @endif
                                    </div>
                                    <label class="block">
                                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-gray-600 file:ml-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 dark:text-gray-300 dark:file:bg-blue-900/30 dark:file:text-blue-200">
                                        <p class="{{ $hintClass }}">PNG/JPG/WebP، حتى 1MB، بأبعاد مناسبة للشعار. يتم التخزين باسم آمن عشوائي.</p>
                                        @if($settings->logo_original_name)
                                            <p class="{{ $hintClass }}">الملف الحالي: {{ $settings->logo_original_name }}</p>
                                        @endif
                                        @error('logo') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-blue-700">حفظ بيانات العيادة</button>
                        </div>
                    </form>
                </section>

                <section id="financial" class="rounded-lg border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                        <h2 class="font-bold text-gray-900 dark:text-white">الإعدادات المالية</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">تغيير رمز العملة لا يحوّل أي مبلغ محفوظ ولا يعدل الفواتير السابقة.</p>
                    </div>
                    <form action="{{ route('settings.update') }}" method="POST" class="p-5">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="financial">

                        <div class="grid gap-5 md:grid-cols-3">
                            <label class="block">
                                <span class="{{ $labelClass }}">كود العملة</span>
                                <input type="text" name="currency_code" value="{{ old('currency_code', $settings->currency_code ?? 'EGP') }}" class="{{ $fieldClass }}" maxlength="3" required>
                                <p class="{{ $hintClass }}">مثال: EGP</p>
                                @error('currency_code') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="{{ $labelClass }}">رمز العملة المعروض</span>
                                <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings->currency_symbol ?? $settings->currency) }}" class="{{ $fieldClass }}" required>
                                <p class="{{ $hintClass }}">يُحفظ أيضًا في الحقل القديم للتوافق مع الفواتير الحالية.</p>
                                @error('currency_symbol') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="{{ $labelClass }}">عمولة الأخصائي الافتراضية %</span>
                                <input type="number" step="0.01" min="0" max="100" name="default_therapist_commission_rate" value="{{ old('default_therapist_commission_rate', $settings->default_therapist_commission_rate) }}" class="{{ $fieldClass }}" required>
                                <p class="{{ $hintClass }}">تطبق على الأخصائي الجديد فقط عند عدم إدخال عمولة مخصصة.</p>
                                @error('default_therapist_commission_rate') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                            سياسات الإلغاء والاسترجاع غير مفعلة هنا لأن النظام لا يملك بعد سير عمل مالي كامل للإلغاء، الاسترداد، والأثر العكسي.
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-blue-700">حفظ الإعدادات المالية</button>
                        </div>
                    </form>
                </section>

                <section id="hours" class="rounded-lg border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                        <h2 class="font-bold text-gray-900 dark:text-white">ساعات العمل</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">يتم حفظها كبنية قابلة للمعالجة، لكنها لا تمنع الحجز خارج المواعيد في هذه المرحلة.</p>
                    </div>
                    <form action="{{ route('settings.update') }}" method="POST" class="p-5">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="working_hours">

                        <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700">
                            <table class="w-full min-w-[680px] text-right">
                                <thead class="bg-gray-50 text-xs font-medium text-gray-500 dark:bg-gray-700/50 dark:text-gray-300">
                                    <tr>
                                        <th class="px-4 py-3">اليوم</th>
                                        <th class="px-4 py-3">حالة العمل</th>
                                        <th class="px-4 py-3">وقت البداية</th>
                                        <th class="px-4 py-3">وقت النهاية</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach(\App\Models\Setting::defaultWorkingHours() as $dayKey => $defaultDay)
                                        @php
                                            $day = $workingHours[$dayKey] ?? $defaultDay;
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $defaultDay['label'] }}</td>
                                            <td class="px-4 py-3">
                                                <input type="hidden" name="working_hours[{{ $dayKey }}][is_open]" value="0">
                                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                                    <input type="checkbox" name="working_hours[{{ $dayKey }}][is_open]" value="1" @checked(old("working_hours.$dayKey.is_open", $day['is_open'] ?? false)) class="rounded border-gray-300 text-blue-600">
                                                    مفتوح
                                                </label>
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="time" name="working_hours[{{ $dayKey }}][opens_at]" value="{{ old("working_hours.$dayKey.opens_at", $day['opens_at'] ?? '') }}" class="{{ $fieldClass }}">
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="time" name="working_hours[{{ $dayKey }}][closes_at]" value="{{ old("working_hours.$dayKey.closes_at", $day['closes_at'] ?? '') }}" class="{{ $fieldClass }}">
                                            </td>
                                        </tr>
                                        @error("working_hours.$dayKey.opens_at") <tr><td colspan="4" class="px-4 pb-2 text-xs text-red-600">{{ $message }}</td></tr> @enderror
                                        @error("working_hours.$dayKey.closes_at") <tr><td colspan="4" class="px-4 pb-2 text-xs text-red-600">{{ $message }}</td></tr> @enderror
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-blue-700">حفظ ساعات العمل</button>
                        </div>
                    </form>
                </section>

                <section id="notifications" class="rounded-lg border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                        <h2 class="font-bold text-gray-900 dark:text-white">الإشعارات والتواصل</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">لا يتم حفظ أي Auth Token أو Secret هنا. الأسرار تبقى في ملف البيئة أو مدير الأسرار.</p>
                    </div>
                    <form action="{{ route('settings.update') }}" method="POST" class="p-5">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="notifications">
                        <input type="hidden" name="appointment_reminders_enabled" value="0">
                        <input type="hidden" name="invoice_notifications_enabled" value="0">

                        <div class="grid gap-5 md:grid-cols-2">
                            <label class="flex items-center gap-2 rounded-lg border border-gray-100 p-3 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="appointment_reminders_enabled" value="1" @checked(old('appointment_reminders_enabled', $settings->appointment_reminders_enabled)) class="rounded border-gray-300 text-blue-600">
                                تفعيل تذكير المواعيد
                            </label>
                            <label class="block">
                                <span class="{{ $labelClass }}">عدد الساعات قبل الموعد</span>
                                <input type="number" min="1" max="168" name="appointment_reminder_hours" value="{{ old('appointment_reminder_hours', $settings->appointment_reminder_hours) }}" class="{{ $fieldClass }}">
                                @error('appointment_reminder_hours') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="flex items-center gap-2 rounded-lg border border-gray-100 p-3 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="invoice_notifications_enabled" value="1" @checked(old('invoice_notifications_enabled', $settings->invoice_notifications_enabled)) class="rounded border-gray-300 text-blue-600">
                                تفعيل إشعارات الفواتير
                            </label>
                            <label class="block">
                                <span class="{{ $labelClass }}">رقم المرسل</span>
                                <input type="text" name="notification_sender_number" value="{{ old('notification_sender_number', $settings->notification_sender_number) }}" class="{{ $fieldClass }}">
                                @error('notification_sender_number') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="block md:col-span-2">
                                <span class="{{ $labelClass }}">اسم المرسل / القناة</span>
                                <input type="text" name="notification_sender_name" value="{{ old('notification_sender_name', $settings->notification_sender_name) }}" class="{{ $fieldClass }}">
                                @error('notification_sender_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="block md:col-span-2">
                                <span class="{{ $labelClass }}">قالب تذكير الموعد</span>
                                <textarea name="appointment_reminder_template" rows="4" class="{{ $fieldClass }}">{{ old('appointment_reminder_template', $settings->appointment_reminder_template ?: $defaultSettings['appointment_reminder_template']) }}</textarea>
                                @error('appointment_reminder_template') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="block md:col-span-2">
                                <span class="{{ $labelClass }}">قالب إشعار الفاتورة</span>
                                <textarea name="invoice_notification_template" rows="4" class="{{ $fieldClass }}">{{ old('invoice_notification_template', $settings->invoice_notification_template ?: $defaultSettings['invoice_notification_template']) }}</textarea>
                                @error('invoice_notification_template') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        <div class="mt-5 rounded-lg border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700/40">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">المتغيرات المسموحة</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($templateVariables as $variable)
                                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-mono text-gray-600 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-600">{{ '{' . $variable . '}' }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-blue-700">حفظ إعدادات الإشعارات</button>
                        </div>
                    </form>
                </section>

                <section id="system" class="rounded-lg border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                        <h2 class="font-bold text-gray-900 dark:text-white">إعدادات النظام</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">الواجهة الحالية عربية مباشرة، لذلك لا يتم تفعيل تعدد اللغات شكليًا.</p>
                    </div>
                    <form action="{{ route('settings.update') }}" method="POST" class="p-5">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="system">

                        <div class="grid gap-5 md:grid-cols-2">
                            <label class="block">
                                <span class="{{ $labelClass }}">لغة الواجهة الافتراضية</span>
                                <select name="default_locale" class="{{ $fieldClass }}">
                                    <option value="ar" @selected(old('default_locale', $settings->default_locale) === 'ar')>العربية</option>
                                </select>
                                <p class="{{ $hintClass }}">دعم لغات إضافية يحتاج ملفات ترجمة كاملة واختبارات RTL/LTR.</p>
                                @error('default_locale') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="{{ $labelClass }}">مدة الاحتفاظ بالمهملات بالأيام</span>
                                <input type="number" min="1" max="365" name="trash_retention_days" value="{{ old('trash_retention_days', $settings->trash_retention_days) }}" class="{{ $fieldClass }}">
                                <p class="{{ $hintClass }}">يوجد Trash للاستعادة، ولا يوجد حذف نهائي تلقائي مفعل من هذا الإعداد حاليًا.</p>
                                @error('trash_retention_days') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-blue-700">حفظ إعدادات النظام</button>
                        </div>
                    </form>
                </section>

                @role('مدير النظام')
                    <section id="backup" class="rounded-lg border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                            <h2 class="font-bold text-gray-900 dark:text-white">إدارة البيانات والنسخ الاحتياطي</h2>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">هذا القسم شديد التقييد ولا يحفظ أسرار قاعدة البيانات أو التخزين.</p>
                        </div>
                        <div class="p-5">
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                                لم يتم تفعيل إعدادات النسخ الاحتياطي من الواجهة لأن النظام يحتاج Command أو Queue Job وسجلات تشغيل وتعامل آمن مع الفشل قبل عرض خيارات تشغيلية هنا.
                            </div>
                        </div>
                    </section>
                @endrole
            </div>
        </div>
    </div>
</x-app-layout>
