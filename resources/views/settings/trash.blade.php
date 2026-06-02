<x-app-layout>
    <x-slot name="title">سلة المحذوفات</x-slot>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">{{ session('success') }}</div>
    @endif

    <div class="space-y-8">

        <!-- المرضى المحذوفين -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">المرضى المحذوفين ({{ $trashedPatients->count() }})</h2>
            @if($trashedPatients->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">اسم الطفل</th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">ولي الأمر</th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">تاريخ الحذف</th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            @foreach($trashedPatients as $patient)
                            <tr>
                                <td class="px-4 py-3 dark:text-white">{{ $patient->name }}</td>
                                <td class="px-4 py-3 dark:text-gray-300">{{ $patient->guardian->name ?? 'تم حذفه أيضاً' }}</td>
                                <td class="px-4 py-3 dark:text-gray-300">{{ $patient->deleted_at->format('Y-m-d') }}</td>
                                <td class="px-4 py-3">
                                    <form action="{{ route('trash.restore') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="type" value="patient">
                                        <input type="hidden" name="id" value="{{ $patient->id }}">
                                        <button type="submit" class="text-green-600 hover:underline font-bold">استعادة</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">لا يوجد مرضى محذوفين</p>
            @endif
        </div>

        <!-- أولياء الأمور المحذوفين -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">أولياء الأمور المحذوفين ({{ $trashedGuardians->count() }})</h2>
            @if($trashedGuardians->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">الاسم</th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">تاريخ الحذف</th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            @foreach($trashedGuardians as $guardian)
                            <tr>
                                <td class="px-4 py-3 dark:text-white">{{ $guardian->name }}</td>
                                <td class="px-4 py-3 dark:text-gray-300">{{ $guardian->deleted_at->format('Y-m-d') }}</td>
                                <td class="px-4 py-3">
                                    <form action="{{ route('trash.restore') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="type" value="guardian">
                                        <input type="hidden" name="id" value="{{ $guardian->id }}">
                                        <button type="submit" class="text-green-600 hover:underline font-bold">استعادة</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">لا يوجد أولياء أمور محذوفين</p>
            @endif
        </div>

        <!-- الفواتير المحذوفة -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">الفواتير المحذوفة ({{ $trashedInvoices->count() }})</h2>
            @if($trashedInvoices->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">رقم الفاتورة</th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">المريض</th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">تاريخ الحذف</th>
                                <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            @foreach($trashedInvoices as $invoice)
                            <tr>
                                <td class="px-4 py-3 dark:text-white">{{ $invoice->invoice_number }}</td>
                                <td class="px-4 py-3 dark:text-gray-300">{{ $invoice->patient->name ?? 'محذوف' }}</td>
                                <td class="px-4 py-3 dark:text-gray-300">{{ $invoice->deleted_at->format('Y-m-d') }}</td>
                                <td class="px-4 py-3">
                                    <form action="{{ route('trash.restore') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="type" value="invoice">
                                        <input type="hidden" name="id" value="{{ $invoice->id }}">
                                        <button type="submit" class="text-green-600 hover:underline font-bold">استعادة</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">لا توجد فواتير محذوفة</p>
            @endif
        </div>

    </div>
</x-app-layout>
