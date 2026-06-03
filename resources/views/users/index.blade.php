<x-app-layout>
    <x-slot name="title">إدارة المستخدمين والصلاحيات</x-slot>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-400 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">المستخدمون والصلاحيات</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">إدارة حسابات الدخول وتعيين الأدوار</p>
        </div>
        <a href="{{ route('users.create') }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            مستخدم جديد
        </a>
    </div>

    {{-- بطاقات الأدوار الأربعة ─ نظرة سريعة --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        @php
            $roleColors = [
                'مدير النظام'     => 'purple',
                'مسؤول مالي'     => 'amber',
                'موظف استقبال'   => 'blue',
                'أخصائي تخاطب'  => 'teal',
            ];
        @endphp
        @foreach($roles as $role)
        @php $color = $roleColors[$role->name] ?? 'gray'; @endphp
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $role->name }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $users->filter(fn($u) => $u->roles->pluck('name')->contains($role->name))->count() }}
            </p>
            <p class="text-xs text-gray-400 mt-1">مستخدم</p>
        </div>
        @endforeach
    </div>

    {{-- جدول المستخدمين --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <table class="w-full text-right">
            <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-3.5 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">المستخدم</th>
                    <th class="px-6 py-3.5 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">الدور</th>
                    <th class="px-6 py-3.5 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">الحالة</th>
                    <th class="px-6 py-3.5 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">تاريخ الإنشاء</th>
                    <th class="px-6 py-3.5 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                @forelse($users as $user)
                @php
                    $role     = $user->roles->first();
                    $roleName = $role?->name ?? 'بدون دور';
                    $color    = $roleColors[$roleName] ?? 'gray';
                    $colorMap = [
                        'purple' => 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
                        'amber'  => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
                        'blue'   => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
                        'teal'   => 'bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400',
                        'gray'   => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
                    ];
                    $therapist = \App\Models\Therapist::where('user_id', $user->id)->first();
                @endphp
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">

                    {{-- المستخدم --}}
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            {{-- Avatar --}}
                            <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 font-medium text-sm
                                @if($color === 'purple') bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300
                                @elseif($color === 'amber') bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300
                                @elseif($color === 'blue') bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300
                                @elseif($color === 'teal') bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300
                                @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 @endif">
                                {{ mb_substr($user->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $user->name }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $user->email }}</p>
                            </div>
                        </div>
                    </td>

                    {{-- الدور --}}
                    <td class="px-6 py-4">
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full {{ $colorMap[$color] }}">
                            {{ $roleName }}
                        </span>
                    </td>

                    {{-- الحالة --}}
                    <td class="px-6 py-4">
                        @if($therapist)
                            @if($therapist->is_active)
                                <span class="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> نشط
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> معطّل
                                </span>
                            @endif
                        @else
                            <span class="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> نشط
                            </span>
                        @endif
                        {{-- علامة الحساب الحالي --}}
                        @if($user->id === auth()->id())
                            <span class="mr-2 text-xs text-blue-500 dark:text-blue-400">(أنت)</span>
                        @endif
                    </td>

                    {{-- تاريخ الإنشاء --}}
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ $user->created_at->format('d/m/Y') }}
                    </td>

                    {{-- إجراءات --}}
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            {{-- تعديل --}}
                            <a href="{{ route('users.edit', $user) }}"
                               class="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition"
                               title="تعديل">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>

                            {{-- تعطيل/تفعيل (للأخصائيين فقط) --}}
                            @if($therapist && $user->id !== auth()->id())
                            <form action="{{ route('users.toggle-active', $user) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="p-1.5 rounded-lg transition
                                            @if($therapist->is_active)
                                                text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30
                                            @else
                                                text-gray-400 hover:text-green-600 dark:hover:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/30
                                            @endif"
                                        title="{{ $therapist->is_active ? 'تعطيل' : 'تفعيل' }}">
                                    @if($therapist->is_active)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @endif
                                </button>
                            </form>
                            @endif

                            {{-- حذف --}}
                            @if($user->id !== auth()->id())
                            <form action="{{ route('users.destroy', $user) }}" method="POST"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا الحساب؟')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition"
                                        title="حذف">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                            @endif

                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">
                        لا توجد مستخدمون بعد
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- بطاقة شرح الأدوار --}}
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="font-bold text-gray-900 dark:text-white mb-4">صلاحيات كل دور</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

            @php
            $roleDetails = [
                ['name' => 'مدير النظام', 'color' => 'purple', 'perms' => ['جميع الصلاحيات كاملة', 'إدارة المستخدمين', 'الإعدادات والتقارير', 'المالية والرواتب']],
                ['name' => 'مسؤول مالي', 'color' => 'amber', 'perms' => ['الفواتير وعروض الأسعار', 'الباقات والرواتب', 'الموردون والمخزن', 'عرض المرضى فقط']],
                ['name' => 'موظف استقبال', 'color' => 'blue', 'perms' => ['تسجيل الحضور بالباركود', 'إنشاء وتعديل مرضى', 'إدارة المواعيد', 'بدون صلاحية مالية']],
                ['name' => 'أخصائي تخاطب', 'color' => 'teal', 'perms' => ['جلساته ومرضاه فقط', 'البرامج والمعالم', 'الواجبات المنزلية', 'بدون وصول للمالية']],
            ];
            @endphp

            @foreach($roleDetails as $rd)
            @php
                $bg  = ['purple'=>'bg-purple-50 dark:bg-purple-900/20', 'amber'=>'bg-amber-50 dark:bg-amber-900/20', 'blue'=>'bg-blue-50 dark:bg-blue-900/20', 'teal'=>'bg-teal-50 dark:bg-teal-900/20'];
                $txt = ['purple'=>'text-purple-700 dark:text-purple-400', 'amber'=>'text-amber-700 dark:text-amber-400', 'blue'=>'text-blue-700 dark:text-blue-400', 'teal'=>'text-teal-700 dark:text-teal-400'];
                $dot = ['purple'=>'bg-purple-400', 'amber'=>'bg-amber-400', 'blue'=>'bg-blue-400', 'teal'=>'bg-teal-400'];
            @endphp
            <div class="rounded-lg p-4 {{ $bg[$rd['color']] }}">
                <p class="font-medium text-sm mb-3 {{ $txt[$rd['color']] }}">{{ $rd['name'] }}</p>
                <ul class="space-y-1.5">
                    @foreach($rd['perms'] as $perm)
                    <li class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $dot[$rd['color']] }}"></span>
                        {{ $perm }}
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
    </div>

</x-app-layout>
