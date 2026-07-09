<x-app-layout>
    <x-slot name="title">إعدادات النظام</x-slot>

    @php
        $fieldClass = 'clinic-field w-full text-sm';
        $labelClass = 'mb-1 block text-sm font-medium text-text';
        $hintClass = 'mt-1 text-xs text-text-muted';
        $errorClass = 'mt-1 block text-xs text-danger';
        $sectionClass = 'clinic-card';
        $sectionHeaderClass = 'border-b border-surface-border px-5 py-4';
        $sectionTitleClass = 'font-bold text-text';
        $sectionDescriptionClass = 'mt-1 text-xs text-text-muted';
        $primaryButtonClass = 'rounded-lg bg-primary px-5 py-2 text-sm font-medium text-primary-contrast transition hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-surface';
        $checkboxClass = 'rounded border-surface-border text-primary focus:ring-primary';
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
            <div class="rounded-lg border border-success bg-success-soft px-4 py-3 text-sm text-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-lg border border-danger bg-danger-soft px-4 py-3 text-sm text-danger">
                يرجى مراجعة الحقول المحددة قبل الحفظ.
            </div>
        @endif

        <div>
            <h1 class="text-2xl font-bold text-text">إعدادات النظام</h1>
            <p class="mt-1 text-sm text-text-muted">إعدادات تشغيلية آمنة للعيادة دون تخزين أسرار أو تغيير بيانات مالية تاريخية.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
            <aside class="{{ $sectionClass }} h-fit p-3">
                <nav class="space-y-1">
                    @foreach($sectionLinks as $href => $label)
                        <a href="{{ $href }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-text-muted transition hover:bg-surface-muted hover:text-text">
                            {{ $label }}
                        </a>
                    @endforeach
                    @role('مدير النظام')
                        <a href="#backup" class="block rounded-lg px-3 py-2 text-sm font-medium text-text-muted transition hover:bg-surface-muted hover:text-text">
                            إدارة البيانات والنسخ الاحتياطي
                        </a>
                    @endrole
                </nav>
            </aside>

            <div class="space-y-6">
                <section id="clinic" class="{{ $sectionClass }}">
                    <div class="{{ $sectionHeaderClass }}">
                        <h2 class="{{ $sectionTitleClass }}">بيانات العيادة</h2>
                        <p class="{{ $sectionDescriptionClass }}">تظهر هذه البيانات في الواجهة والتقارير الرسمية عند دعمها.</p>
                    </div>
                    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="p-5">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="clinic">

                        <div class="grid gap-5 md:grid-cols-2">
                            <label class="block md:col-span-2">
                                <span class="{{ $labelClass }}">اسم العيادة / المركز</span>
                                <input type="text" name="clinic_name" value="{{ old('clinic_name', $settings->clinic_name) }}" class="{{ $fieldClass }}" required>
                                @error('clinic_name') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                            </label>

                            <label class="block md:col-span-2">
                                <span class="{{ $labelClass }}">العنوان</span>
                                <input type="text" name="address" value="{{ old('address', $settings->address) }}" class="{{ $fieldClass }}">
                                @error('address') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="{{ $labelClass }}">رقم الهاتف الرسمي</span>
                                <input type="text" name="official_phone" value="{{ old('official_phone', $settings->official_phone) }}" class="{{ $fieldClass }}">
                                @error('official_phone') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="{{ $labelClass }}">البريد الإلكتروني الرسمي</span>
                                <input type="email" name="official_email" value="{{ old('official_email', $settings->official_email) }}" class="{{ $fieldClass }}">
                                @error('official_email') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="{{ $labelClass }}">الرقم الضريبي</span>
                                <input type="text" name="tax_number" value="{{ old('tax_number', $settings->tax_number) }}" class="{{ $fieldClass }}">
                                @error('tax_number') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="{{ $labelClass }}">السجل التجاري</span>
                                <input type="text" name="commercial_registration" value="{{ old('commercial_registration', $settings->commercial_registration) }}" class="{{ $fieldClass }}">
                                @error('commercial_registration') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                            </label>

                            <div class="md:col-span-2">
                                <span class="{{ $labelClass }}">شعار العيادة</span>
                                <div class="grid gap-4 rounded-lg border border-surface-border p-4 md:grid-cols-[160px_1fr] md:items-center">
                                    <div class="flex h-28 items-center justify-center rounded-lg bg-surface-muted">
                                        @if($settings->logo_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($settings->logo_path) }}" alt="شعار العيادة" class="max-h-24 max-w-32 object-contain">
                                        @else
                                            <span class="text-sm text-text-subtle">لا يوجد شعار</span>
                                        @endif
                                    </div>
                                    <label class="block">
                                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-text-muted file:ml-4 file:rounded-lg file:border-0 file:bg-primary-soft file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary hover:file:bg-surface-muted">
                                        <p class="{{ $hintClass }}">PNG/JPG/WebP، حتى 1MB، بأبعاد مناسبة للشعار. يتم التخزين باسم آمن عشوائي.</p>
                                        @if($settings->logo_original_name)
                                            <p class="{{ $hintClass }}">الملف الحالي: {{ $settings->logo_original_name }}</p>
                                        @endif
                                        @error('logo') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button type="submit" class="{{ $primaryButtonClass }}">حفظ بيانات العيادة</button>
                        </div>
                    </form>
                </section>

                <section id="financial" class="{{ $sectionClass }}">
                    <div class="{{ $sectionHeaderClass }}">
                        <h2 class="{{ $sectionTitleClass }}">الإعدادات المالية</h2>
                        <p class="{{ $sectionDescriptionClass }}">تغيير رمز العملة لا يحوّل أي مبلغ محفوظ ولا يعدل الفواتير السابقة.</p>
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
                                @error('currency_code') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="{{ $labelClass }}">رمز العملة المعروض</span>
                                <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings->currency_symbol ?? $settings->currency) }}" class="{{ $fieldClass }}" required>
                                <p class="{{ $hintClass }}">يُحفظ أيضًا في الحقل القديم للتوافق مع الفواتير الحالية.</p>
                                @error('currency_symbol') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="{{ $labelClass }}">عمولة الأخصائي الافتراضية %</span>
                                <input type="number" step="0.01" min="0" max="100" name="default_therapist_commission_rate" value="{{ old('default_therapist_commission_rate', $settings->default_therapist_commission_rate) }}" class="{{ $fieldClass }}" required>
                                <p class="{{ $hintClass }}">تطبق على الأخصائي الجديد فقط عند عدم إدخال عمولة مخصصة.</p>
                                @error('default_therapist_commission_rate') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        <div class="mt-5 rounded-lg border border-warning bg-warning-soft p-4 text-sm text-warning">
                            سياسات الإلغاء والاسترجاع غير مفعلة هنا لأن النظام لا يملك بعد سير عمل مالي كامل للإلغاء، الاسترداد، والأثر العكسي.
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button type="submit" class="{{ $primaryButtonClass }}">حفظ الإعدادات المالية</button>
                        </div>
                    </form>
                </section>

                <section id="hours" class="{{ $sectionClass }}">
                    <div class="{{ $sectionHeaderClass }}">
                        <h2 class="{{ $sectionTitleClass }}">ساعات العمل</h2>
                        <p class="{{ $sectionDescriptionClass }}">يتم حفظها كبنية قابلة للمعالجة، لكنها لا تمنع الحجز خارج المواعيد في هذه المرحلة.</p>
                    </div>
                    <form action="{{ route('settings.update') }}" method="POST" class="p-5">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="working_hours">

                        <div class="overflow-x-auto rounded-lg border border-surface-border">
                            <table class="w-full min-w-[680px] text-right">
                                <thead class="bg-surface-muted text-xs font-medium text-text-muted">
                                    <tr>
                                        <th class="px-4 py-3">اليوم</th>
                                        <th class="px-4 py-3">حالة العمل</th>
                                        <th class="px-4 py-3">وقت البداية</th>
                                        <th class="px-4 py-3">وقت النهاية</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-surface-border">
                                    @foreach(\App\Models\Setting::defaultWorkingHours() as $dayKey => $defaultDay)
                                        @php
                                            $day = $workingHours[$dayKey] ?? $defaultDay;
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-text">{{ $defaultDay['label'] }}</td>
                                            <td class="px-4 py-3">
                                                <input type="hidden" name="working_hours[{{ $dayKey }}][is_open]" value="0">
                                                <label class="inline-flex items-center gap-2 text-sm text-text-muted">
                                                    <input type="checkbox" name="working_hours[{{ $dayKey }}][is_open]" value="1" @checked(old("working_hours.$dayKey.is_open", $day['is_open'] ?? false)) class="{{ $checkboxClass }}">
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
                                        @error("working_hours.$dayKey.opens_at") <tr><td colspan="4" class="px-4 pb-2 text-xs text-danger">{{ $message }}</td></tr> @enderror
                                        @error("working_hours.$dayKey.closes_at") <tr><td colspan="4" class="px-4 pb-2 text-xs text-danger">{{ $message }}</td></tr> @enderror
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button type="submit" class="{{ $primaryButtonClass }}">حفظ ساعات العمل</button>
                        </div>
                    </form>
                </section>

                <section id="notifications" class="{{ $sectionClass }}">
                    <div class="{{ $sectionHeaderClass }}">
                        <h2 class="{{ $sectionTitleClass }}">الإشعارات والتواصل</h2>
                        <p class="{{ $sectionDescriptionClass }}">لا يتم حفظ أي Auth Token أو Secret هنا. الأسرار تبقى في ملف البيئة أو مدير الأسرار.</p>
                    </div>
                    <form action="{{ route('settings.update') }}" method="POST" class="p-5">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="notifications">
                        <input type="hidden" name="appointment_reminders_enabled" value="0">
                        <input type="hidden" name="invoice_notifications_enabled" value="0">

                        <div class="grid gap-5 md:grid-cols-2">
                            <label class="flex items-center gap-2 rounded-lg border border-surface-border p-3 text-sm text-text-muted">
                                <input type="checkbox" name="appointment_reminders_enabled" value="1" @checked(old('appointment_reminders_enabled', $settings->appointment_reminders_enabled)) class="{{ $checkboxClass }}">
                                تفعيل تذكير المواعيد
                            </label>
                            <label class="block">
                                <span class="{{ $labelClass }}">عدد الساعات قبل الموعد</span>
                                <input type="number" min="1" max="168" name="appointment_reminder_hours" value="{{ old('appointment_reminder_hours', $settings->appointment_reminder_hours) }}" class="{{ $fieldClass }}">
                                @error('appointment_reminder_hours') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                            </label>
                            <label class="flex items-center gap-2 rounded-lg border border-surface-border p-3 text-sm text-text-muted">
                                <input type="checkbox" name="invoice_notifications_enabled" value="1" @checked(old('invoice_notifications_enabled', $settings->invoice_notifications_enabled)) class="{{ $checkboxClass }}">
                                تفعيل إشعارات الفواتير
                            </label>
                            <label class="block">
                                <span class="{{ $labelClass }}">رقم المرسل</span>
                                <input type="text" name="notification_sender_number" value="{{ old('notification_sender_number', $settings->notification_sender_number) }}" class="{{ $fieldClass }}">
                                @error('notification_sender_number') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                            </label>
                            <label class="block md:col-span-2">
                                <span class="{{ $labelClass }}">اسم المرسل / القناة</span>
                                <input type="text" name="notification_sender_name" value="{{ old('notification_sender_name', $settings->notification_sender_name) }}" class="{{ $fieldClass }}">
                                @error('notification_sender_name') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                            </label>
                            <label class="block md:col-span-2">
                                <span class="{{ $labelClass }}">قالب تذكير الموعد</span>
                                <textarea name="appointment_reminder_template" rows="4" class="{{ $fieldClass }}">{{ old('appointment_reminder_template', $settings->appointment_reminder_template ?: $defaultSettings['appointment_reminder_template']) }}</textarea>
                                @error('appointment_reminder_template') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                            </label>
                            <label class="block md:col-span-2">
                                <span class="{{ $labelClass }}">قالب إشعار الفاتورة</span>
                                <textarea name="invoice_notification_template" rows="4" class="{{ $fieldClass }}">{{ old('invoice_notification_template', $settings->invoice_notification_template ?: $defaultSettings['invoice_notification_template']) }}</textarea>
                                @error('invoice_notification_template') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        <div class="mt-5 rounded-lg border border-surface-border bg-surface-muted p-4">
                            <p class="text-sm font-medium text-text">المتغيرات المسموحة</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($templateVariables as $variable)
                                    <span class="rounded-full bg-surface-elevated px-2.5 py-1 text-xs font-mono text-text-muted ring-1 ring-surface-border">{{ '{' . $variable . '}' }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button type="submit" class="{{ $primaryButtonClass }}">حفظ إعدادات الإشعارات</button>
                        </div>
                    </form>
                </section>

                <section id="system" class="{{ $sectionClass }}">
                    <div class="{{ $sectionHeaderClass }}">
                        <h2 class="{{ $sectionTitleClass }}">إعدادات النظام</h2>
                        <p class="{{ $sectionDescriptionClass }}">الواجهة الحالية عربية مباشرة، لذلك لا يتم تفعيل تعدد اللغات شكليًا.</p>
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
                                @error('default_locale') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="{{ $labelClass }}">مدة الاحتفاظ بالمهملات بالأيام</span>
                                <input type="number" min="1" max="365" name="trash_retention_days" value="{{ old('trash_retention_days', $settings->trash_retention_days) }}" class="{{ $fieldClass }}">
                                <p class="{{ $hintClass }}">يوجد Trash للاستعادة، ولا يوجد حذف نهائي تلقائي مفعل من هذا الإعداد حاليًا.</p>
                                @error('trash_retention_days') <span class="{{ $errorClass }}">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button type="submit" class="{{ $primaryButtonClass }}">حفظ إعدادات النظام</button>
                        </div>
                    </form>
                </section>

                @role('مدير النظام')
                    <section id="backup" class="{{ $sectionClass }}">
                        <div class="{{ $sectionHeaderClass }}">
                            <h2 class="{{ $sectionTitleClass }}">إدارة البيانات والنسخ الاحتياطي</h2>
                            <p class="{{ $sectionDescriptionClass }}">هذا القسم شديد التقييد ولا يحفظ أسرار قاعدة البيانات أو التخزين.</p>
                        </div>
                        <div class="p-5">
                            <div class="rounded-lg border border-warning bg-warning-soft p-4 text-sm text-warning">
                                لم يتم تفعيل إعدادات النسخ الاحتياطي من الواجهة لأن النظام يحتاج Command أو Queue Job وسجلات تشغيل وتعامل آمن مع الفشل قبل عرض خيارات تشغيلية هنا.
                            </div>
                        </div>
                    </section>
                @endrole
            </div>
        </div>
    </div>
</x-app-layout>
