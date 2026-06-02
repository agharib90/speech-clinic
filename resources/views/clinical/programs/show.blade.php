<x-app-layout>
    <x-slot name="title">برنامج: {{ $program->patient->name }}</x-slot>

    <!-- رسائل النجاح -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">{{ session('success') }}</div>
    @endif

    <div class="mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">برنامج: {{ $program->patient->name }}</h2>
        <p class="text-gray-600 dark:text-gray-400">الأخصائي: {{ $program->therapist->name }} | الأهداف: {{ $program->goals }}</p>
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
