<x-app-layout>
    <x-slot name="title">برنامج: {{ $program->patient->name }}</x-slot>

    <!-- رسائل النجاح -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">{{ session('success') }}</div>
    @endif

    <!-- ترويسة الصفحة مع زر الـ PDF -->
    <div class="mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">برنامج: {{ $program->patient->name }}</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">الأخصائي: {{ $program->therapist->name ?? 'غير محدد' }} | الأهداف: {{ $program->goals }}</p>
        </div>

        <!-- زر تحميل التقرير -->
        <div class="flex flex-wrap gap-2">
            @can('view patients')
                <a href="{{ route('patients.workspace', $program->patient) }}" class="clinic-btn-secondary">رجوع إلى ملف الحالة</a>
            @endcan
            <a href="{{ route('programs.progress-report', $program->id) }}"
               class="inline-flex items-center justify-center px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition-colors shadow-sm">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                تحميل تقرير التطور (PDF)
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- القسم الأيمن: إضافة بيانات -->
        <div class="lg:col-span-1 space-y-6">

            <!-- ١. إضافة تقييم (Milestone) -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                <h3 class="font-bold mb-3 dark:text-gray-200">تسجيل تقييم جديد (تتبع التطور)</h3>
                <form action="{{ route('milestones.store', $program) }}" method="POST">
                    @csrf
                    <div class="space-y-3">
                        <select name="skill_area" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                            <option value="نطق">نطق</option>
                            <option value="لغة">لغة</option>
                            <option value="صوت">صوت</option>
                            <option value="تواصل">تواصل</option>
                        </select>
                        <input type="number" name="baseline_score" placeholder="درجة الخط القاعدي (0-100)" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                        <input type="number" name="current_score" placeholder="الدرجة الحالية (0-100)" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded">تسجيل</button>
                    </div>
                </form>
            </div>

            @can('edit therapy')
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                <h3 class="font-bold mb-3 dark:text-gray-200">نقطة تقدم زمنية</h3>
                <form action="{{ route('programs.progress-points.store', $program) }}" method="POST">
                    @csrf
                    <div class="space-y-3">
                        <input type="date" name="recorded_at" value="{{ now()->toDateString() }}" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                        <select name="domain" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                            <option value="النطق">النطق</option>
                            <option value="اللغة الاستقبالية">اللغة الاستقبالية</option>
                            <option value="اللغة التعبيرية">اللغة التعبيرية</option>
                            <option value="الطلاقة">الطلاقة</option>
                            <option value="التواصل الاجتماعي">التواصل الاجتماعي</option>
                        </select>
                        <input type="number" name="score" min="0" max="100" step="0.01" placeholder="الدرجة من 0 إلى 100" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                        <textarea name="notes" rows="2" placeholder="ملاحظة مختصرة" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200"></textarea>
                        <button type="submit" class="w-full bg-emerald-600 text-white py-2 rounded">حفظ نقطة التقدم</button>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                <h3 class="font-bold mb-3 dark:text-gray-200">مقياس شدة التأتأة</h3>
                <form action="{{ route('programs.stuttering-assessments.store', $program) }}" method="POST">
                    @csrf
                    <div class="space-y-3">
                        <input type="date" name="assessed_at" value="{{ now()->toDateString() }}" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                        <input type="text" name="sample_context" placeholder="سياق العينة: قراءة، حوار، لعب..." class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="syllables_count" min="0" placeholder="عدد المقاطع" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                            <input type="number" name="stuttered_syllables_count" min="0" placeholder="مقاطع متأتأة" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="duration_score" min="0" max="10" placeholder="درجة المدة 0-10" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                            <input type="number" name="physical_concomitants_score" min="0" max="10" placeholder="مصاحبات 0-10" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                        </div>
                        <textarea name="notes" rows="2" placeholder="ملاحظات المقياس" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200"></textarea>
                        <button type="submit" class="w-full bg-amber-600 text-white py-2 rounded">حفظ المقياس</button>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                <h3 class="font-bold mb-3 dark:text-gray-200">ملخص خروج من البرنامج</h3>
                <form action="{{ route('programs.discharge-summary.store', $program) }}" method="POST">
                    @csrf
                    <div class="space-y-3">
                        <input type="date" name="discharge_date" value="{{ old('discharge_date', optional($program->dischargeSummary?->discharge_date)->format('Y-m-d') ?? now()->toDateString()) }}" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                        <input type="text" name="reason" value="{{ old('reason', $program->dischargeSummary->reason ?? '') }}" placeholder="سبب الخروج" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                        <textarea name="initial_status" rows="2" placeholder="الوضع عند بداية البرنامج" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">{{ old('initial_status', $program->dischargeSummary->initial_status ?? '') }}</textarea>
                        <textarea name="final_status" rows="2" placeholder="الوضع عند الخروج" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">{{ old('final_status', $program->dischargeSummary->final_status ?? '') }}</textarea>
                        <textarea name="goals_outcome" rows="2" placeholder="نتيجة الأهداف العلاجية" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">{{ old('goals_outcome', $program->dischargeSummary->goals_outcome ?? '') }}</textarea>
                        <textarea name="recommendations" rows="2" placeholder="التوصيات المنزلية أو المدرسية" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">{{ old('recommendations', $program->dischargeSummary->recommendations ?? '') }}</textarea>
                        <textarea name="follow_up_plan" rows="2" placeholder="خطة المتابعة" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">{{ old('follow_up_plan', $program->dischargeSummary->follow_up_plan ?? '') }}</textarea>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="mark_completed" value="1" class="rounded">
                            <span>إغلاق البرنامج كبرنامج مكتمل</span>
                        </label>
                        <button type="submit" class="w-full bg-slate-700 text-white py-2 rounded">حفظ ملخص الخروج</button>
                    </div>
                </form>
            </div>
            @endcan

            <!-- ٢. رفع مرفق (تسجيل قبل/بعد) -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                <h3 class="font-bold mb-3 dark:text-gray-200">رفع مرفق (صوت/صورة/فيديو)</h3>
                <form action="{{ route('attachments.store', $program) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="space-y-3">
                        <select name="attachment_type" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                            <option value="مرفق_عام">مرفق عام</option>
                            <option value="تسجيل_قبل">تسجيل (قبل العلاج)</option>
                            <option value="تسجيل_بعد">تسجيل (بعد العلاج)</option>
                        </select>
                        <input type="file" name="file" class="w-full border rounded px-3 py-1 dark:bg-gray-700 dark:text-gray-200" required>
                        <input type="text" name="description" placeholder="وصف مختصر..." class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                        <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded">رفع الملف</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- القسم الأيسر: عرض البيانات العيادية -->
        <div class="lg:col-span-2 space-y-6">

            @php
                $progressSeries = $program->progressPoints->groupBy('domain');
                $chartColors = ['#2563eb', '#059669', '#d97706', '#7c3aed', '#dc2626'];
                $latestArticulation = $program->articulationAssessments->first();
                $latestStuttering = $program->stutteringAssessments->first();
            @endphp

            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
                    <h3 class="font-bold dark:text-gray-200">ملخص تاريخ الحالة</h3>
                    <a href="{{ route('patients.show', $program->patient) }}" class="text-sm text-blue-600 hover:underline">فتح ملف المريض</a>
                </div>
                @if($program->patient->caseHistory)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-300">
                        <p><span class="font-bold">الشكوى:</span> {{ $program->patient->caseHistory->main_concerns ?: 'غير مسجلة' }}</p>
                        <p><span class="font-bold">المراحل التطورية:</span> {{ $program->patient->caseHistory->developmental_milestones ?: 'غير مسجلة' }}</p>
                        <p><span class="font-bold">السمع والبصر:</span> {{ $program->patient->caseHistory->hearing_vision_notes ?: 'غير مسجلة' }}</p>
                        <p><span class="font-bold">تدخلات سابقة:</span> {{ $program->patient->caseHistory->previous_interventions ?: 'غير مسجلة' }}</p>
                    </div>
                @else
                    <p class="text-sm text-gray-500">لا يوجد تاريخ حالة مسجل لهذا المريض بعد.</p>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                <h3 class="font-bold mb-4 dark:text-gray-200">رسم التقدم عبر الوقت</h3>
                @if($progressSeries->count())
                    <div class="space-y-5">
                        @foreach($progressSeries as $domain => $points)
                            @php
                                $points = $points->values();
                                $plotPoints = $points->map(function ($point, $index) use ($points) {
                                    $x = $points->count() === 1 ? 50 : round(($index / max($points->count() - 1, 1)) * 100, 2);
                                    $y = round(100 - (float) $point->score, 2);

                                    return "{$x},{$y}";
                                })->implode(' ');
                                $color = $chartColors[$loop->index % count($chartColors)];
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $domain }}</h4>
                                    <span class="text-xs text-gray-500">{{ $points->last()->score }}%</span>
                                </div>
                                <div class="h-36 border border-gray-200 dark:border-gray-700 rounded bg-gray-50 dark:bg-gray-900 p-3">
                                    <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="w-full h-full">
                                        <line x1="0" y1="25" x2="100" y2="25" stroke="#e5e7eb" stroke-width="0.7" />
                                        <line x1="0" y1="50" x2="100" y2="50" stroke="#e5e7eb" stroke-width="0.7" />
                                        <line x1="0" y1="75" x2="100" y2="75" stroke="#e5e7eb" stroke-width="0.7" />
                                        <polyline points="{{ $plotPoints }}" fill="none" stroke="{{ $color }}" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                                        @foreach($points as $pointIndex => $point)
                                            @php
                                                $x = $points->count() === 1 ? 50 : round(($pointIndex / max($points->count() - 1, 1)) * 100, 2);
                                                $y = round(100 - (float) $point->score, 2);
                                            @endphp
                                            <circle cx="{{ $x }}" cy="{{ $y }}" r="2.1" fill="{{ $color }}" />
                                        @endforeach
                                    </svg>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-2 text-xs text-gray-500">
                                    @foreach($points as $point)
                                        <span>{{ $point->recorded_at->format('Y-m-d') }}: {{ $point->score }}%</span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-sm">لا توجد نقاط تقدم زمنية بعد.</p>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
                    <h3 class="font-bold dark:text-gray-200">بنك اختبار النطق العربي</h3>
                    @if($latestArticulation)
                        <span class="text-xs px-3 py-1 rounded-full bg-blue-100 text-blue-700">
                            آخر دقة: {{ $latestArticulation->accuracy_percent }}%
                        </span>
                    @endif
                </div>

                @can('edit therapy')
                <details class="mb-5">
                    <summary class="cursor-pointer text-sm text-blue-600 font-semibold">تسجيل اختبار جديد</summary>
                    <form action="{{ route('programs.articulation-assessments.store', $program) }}" method="POST" class="mt-4 space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <input type="date" name="assessed_at" value="{{ now()->toDateString() }}" class="border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                            <input type="text" name="title" value="اختبار النطق العربي" class="border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                            <input type="text" name="notes" placeholder="ملاحظة عامة" class="border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-gray-600 dark:text-gray-300">
                                    <tr>
                                        <th class="text-right py-2">الصوت</th>
                                        <th class="text-right py-2">كلمة الاختبار</th>
                                        <th class="text-right py-2">الأداء</th>
                                        <th class="text-right py-2">الإبدال</th>
                                        <th class="text-right py-2">ملاحظة</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y dark:divide-gray-700">
                                    @foreach($soundBank as $index => $item)
                                        <tr>
                                            <td class="py-2 font-bold dark:text-gray-200">
                                                {{ $item['sound'] }}
                                                <input type="hidden" name="responses[{{ $index }}][sound]" value="{{ $item['sound'] }}">
                                                <input type="hidden" name="responses[{{ $index }}][position]" value="{{ $item['position'] }}">
                                                <input type="hidden" name="responses[{{ $index }}][prompt_word]" value="{{ $item['prompt_word'] }}">
                                            </td>
                                            <td class="py-2 dark:text-gray-300">{{ $item['prompt_word'] }}</td>
                                            <td class="py-2">
                                                <select name="responses[{{ $index }}][status]" class="border rounded px-2 py-1 dark:bg-gray-700 dark:text-gray-200" required>
                                                    @foreach($articulationStatusLabels as $status => $label)
                                                        <option value="{{ $status }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="py-2">
                                                <input type="text" name="responses[{{ $index }}][substitution]" class="w-24 border rounded px-2 py-1 dark:bg-gray-700 dark:text-gray-200">
                                            </td>
                                            <td class="py-2">
                                                <input type="text" name="responses[{{ $index }}][notes]" class="w-36 border rounded px-2 py-1 dark:bg-gray-700 dark:text-gray-200">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded">حفظ اختبار النطق</button>
                    </form>
                </details>
                @endcan

                @if($latestArticulation)
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4 text-center">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-300">الإجمالي</p>
                            <p class="font-bold dark:text-white">{{ $latestArticulation->total_items }}</p>
                        </div>
                        <div class="bg-green-50 dark:bg-gray-700 rounded p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-300">صحيح</p>
                            <p class="font-bold text-green-700">{{ $latestArticulation->correct_count }}</p>
                        </div>
                        <div class="bg-yellow-50 dark:bg-gray-700 rounded p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-300">مبدل</p>
                            <p class="font-bold text-yellow-700">{{ $latestArticulation->substitution_count }}</p>
                        </div>
                        <div class="bg-red-50 dark:bg-gray-700 rounded p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-300">محذوف</p>
                            <p class="font-bold text-red-700">{{ $latestArticulation->omission_count }}</p>
                        </div>
                        <div class="bg-purple-50 dark:bg-gray-700 rounded p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-300">مشوه</p>
                            <p class="font-bold text-purple-700">{{ $latestArticulation->distortion_count }}</p>
                        </div>
                    </div>
                    @php
                        $articulationErrors = collect($latestArticulation->responses)
                            ->reject(fn ($response) => ($response['status'] ?? null) === 'correct')
                            ->values();
                    @endphp
                    @if($articulationErrors->count())
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            <h4 class="font-bold mb-2">الأصوات التي تحتاج متابعة</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($articulationErrors as $response)
                                    <span class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-700">
                                        {{ $response['sound'] }}: {{ $articulationStatusLabels[$response['status']] ?? $response['status'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <p class="text-gray-500 text-sm">لم يتم تسجيل اختبار نطق بعد.</p>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                <h3 class="font-bold mb-4 dark:text-gray-200">آخر قياس للتأتأة</h3>
                @if($latestStuttering)
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-300">النسبة</p>
                            <p class="font-bold dark:text-white">{{ $latestStuttering->stuttering_percentage }}%</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-300">درجة التكرار</p>
                            <p class="font-bold dark:text-white">{{ $latestStuttering->frequency_score }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-300">الدرجة الكلية</p>
                            <p class="font-bold dark:text-white">{{ $latestStuttering->total_score }}</p>
                        </div>
                        <div class="bg-amber-50 dark:bg-gray-700 rounded p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-300">الشدة</p>
                            <p class="font-bold text-amber-700">{{ $latestStuttering->severity }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500 text-sm">لم يتم تسجيل مقياس تأتأة بعد.</p>
                @endif
            </div>

            @if($program->dischargeSummary)
                <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h3 class="font-bold dark:text-gray-200">ملخص الخروج</h3>
                        <span class="text-xs px-3 py-1 rounded-full bg-slate-100 text-slate-700">
                            {{ $program->dischargeSummary->discharge_date->format('Y-m-d') }}
                        </span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-300">
                        <p><span class="font-bold">السبب:</span> {{ $program->dischargeSummary->reason ?: 'غير محدد' }}</p>
                        <p><span class="font-bold">نتيجة الأهداف:</span> {{ $program->dischargeSummary->goals_outcome ?: 'غير مسجلة' }}</p>
                        <p><span class="font-bold">الوضع النهائي:</span> {{ $program->dischargeSummary->final_status ?: 'غير مسجل' }}</p>
                        <p><span class="font-bold">خطة المتابعة:</span> {{ $program->dischargeSummary->follow_up_plan ?: 'غير مسجلة' }}</p>
                    </div>
                </div>
            @endif

            <!-- عرض التطور (رسم بياني بصري مبسط) -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                <h3 class="font-bold mb-4 dark:text-gray-200">مسار التطور</h3>
                @if($program->milestones->count() > 0)
                    <div class="space-y-4">
                        @foreach($program->milestones->groupBy('skill_area') as $area => $milestones)
                            <div>
                                <h4 class="text-sm font-bold text-gray-600 dark:text-gray-400 mb-1">{{ $area }}</h4>
                                @foreach($milestones as $m)
                                    <div class="flex items-center mb-1">
                                        <span class="text-xs w-20 dark:text-gray-300">{{ $m->recorded_at->format('m/d') }}</span>
                                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $m->current_score }}%"></div>
                                        </div>
                                        <span class="text-xs w-12 text-right font-bold dark:text-white">{{ $m->current_score }}%</span>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-sm">لا توجد تسجيلات تطور بعد.</p>
                @endif
            </div>

            <!-- عرض المرفقات (قبل/بعد) -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                <h3 class="font-bold mb-4 dark:text-gray-200">المرفقات والتسجيلات</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($program->attachments as $att)
                        <div class="border dark:border-gray-700 rounded-lg p-2 text-center">
                            @if($att->attachment_type == 'تسجيل_قبل')
                                <span class="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded-full">قبل</span>
                            @elseif($att->attachment_type == 'تسجيل_بعد')
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">بعد</span>
                            @else
                                <span class="text-xs bg-gray-100 text-gray-800 px-2 py-0.5 rounded-full">عام</span>
                            @endif

                            <a href="{{ Storage::url($att->file_path) }}" target="_blank" class="block mt-2 text-blue-600 text-sm hover:underline truncate">
                                {{ $att->file_name }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- عرض الجلسات والواجبات -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow">
                <h3 class="font-bold mb-4 dark:text-gray-200">سجل الجلسات والواجبات</h3>
                @foreach($program->sessions as $session)
                    <div class="border-b dark:border-gray-700 py-3">
                        <div class="flex justify-between items-center">
                            <h4 class="font-bold dark:text-white">جلسة #{{ $session->session_number }} - {{ $session->session_date }}</h4>
                            <span class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700">{{ $session->status }}</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">الأهداف المحققة: {{ $session->goals_achieved }}</p>

                        <!-- الواجبات المنزلية -->
                        @if($session->homeTasks->count() > 0)
                            <div class="mt-2 bg-gray-50 dark:bg-gray-700 p-2 rounded text-sm">
                                @foreach($session->homeTasks as $task)
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="dark:text-gray-300">📌 {{ $task->description }}</span>
                                        <div class="flex items-center space-x-reverse space-x-2">
                                            <span class="text-xs {{ $task->is_completed ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $task->is_completed ? 'مكتمل' : 'غير مكتمل' }}
                                            </span>
                                            <form action="{{ route('tasks.feedback', $task) }}" method="POST">
                                                @csrf
                                                <button type="submit" name="is_completed" value="1" class="text-xs text-blue-600 hover:underline" onclick="return confirm('تأكيد اكتمال الواجب؟')">تم الإنجاز</button>
                                            </form>
                                        </div>
                                    </div>
                                    @if($task->parent_feedback)
                                        <p class="text-xs text-blue-500 mt-1 italic mb-1">💬 ملاحظة ولي الأمر: "{{ $task->parent_feedback }}"</p>
                                    @else
                                        <form action="{{ route('tasks.feedback', $task) }}" method="POST" class="flex items-center space-x-reverse space-x-1 mb-1">
                                            @csrf
                                            <input type="text" name="parent_feedback" placeholder="أضف ملاحظة ولي الأمر..." class="flex-1 text-xs border rounded px-2 py-1 dark:bg-gray-600 dark:text-gray-200">
                                            <button type="submit" class="text-xs bg-blue-500 text-white px-2 py-1 rounded">حفظ</button>
                                        </form>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <!-- فورم إضافة واجب جديد لهذه الجلسة -->
                        <form action="{{ route('tasks.store') }}" method="POST" class="mt-2 flex items-center space-x-reverse space-x-2">
                            @csrf
                            <input type="hidden" name="therapy_session_id" value="{{ $session->id }}">
                            <input type="text" name="description" placeholder="أضف واجب منزلي جديد..." class="flex-1 text-sm border rounded px-3 py-1 dark:bg-gray-700 dark:text-gray-200" required>
                            <input type="date" name="due_date" class="text-sm border rounded px-2 py-1 dark:bg-gray-700 dark:text-gray-200">
                            <button type="submit" class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded">إضافة</button>
                        </form>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>
