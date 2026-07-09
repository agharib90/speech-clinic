<x-app-layout>
    <x-slot name="title">سلة المحذوفات</x-slot>

    @if(session('success'))
        <div class="relative mb-4 rounded-lg border border-success bg-success-soft px-4 py-3 text-success">{{ session('success') }}</div>
    @endif

    <div class="space-y-8">

        <!-- المرضى المحذوفين -->
        <div class="clinic-card p-6">
            <h2 class="mb-4 text-xl font-bold text-text">المرضى المحذوفين ({{ $trashedPatients->count() }})</h2>
            @if($trashedPatients->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-surface-muted text-text-muted">
                            <tr>
                                <th class="px-4 py-3 text-xs font-medium uppercase">اسم الطفل</th>
                                <th class="px-4 py-3 text-xs font-medium uppercase">ولي الأمر</th>
                                <th class="px-4 py-3 text-xs font-medium uppercase">تاريخ الحذف</th>
                                <th class="px-4 py-3 text-xs font-medium uppercase">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border">
                            @foreach($trashedPatients as $patient)
                            <tr>
                                <td class="px-4 py-3 text-text">{{ $patient->name }}</td>
                                <td class="px-4 py-3 text-text-muted">{{ $patient->guardian->name ?? 'تم حذفه أيضاً' }}</td>
                                <td class="px-4 py-3 text-text-muted">{{ $patient->deleted_at->format('Y-m-d') }}</td>
                                <td class="px-4 py-3">
                                    <form action="{{ route('trash.restore') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="type" value="patient">
                                        <input type="hidden" name="id" value="{{ $patient->id }}">
                                        <button type="submit" class="font-bold text-success hover:underline">استعادة</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="py-4 text-center text-text-muted">لا يوجد مرضى محذوفين</p>
            @endif
        </div>

        <!-- أولياء الأمور المحذوفين -->
        <div class="clinic-card p-6">
            <h2 class="mb-4 text-xl font-bold text-text">أولياء الأمور المحذوفين ({{ $trashedGuardians->count() }})</h2>
            @if($trashedGuardians->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-surface-muted text-text-muted">
                            <tr>
                                <th class="px-4 py-3 text-xs font-medium uppercase">الاسم</th>
                                <th class="px-4 py-3 text-xs font-medium uppercase">تاريخ الحذف</th>
                                <th class="px-4 py-3 text-xs font-medium uppercase">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border">
                            @foreach($trashedGuardians as $guardian)
                            <tr>
                                <td class="px-4 py-3 text-text">{{ $guardian->name }}</td>
                                <td class="px-4 py-3 text-text-muted">{{ $guardian->deleted_at->format('Y-m-d') }}</td>
                                <td class="px-4 py-3">
                                    <form action="{{ route('trash.restore') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="type" value="guardian">
                                        <input type="hidden" name="id" value="{{ $guardian->id }}">
                                        <button type="submit" class="font-bold text-success hover:underline">استعادة</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="py-4 text-center text-text-muted">لا يوجد أولياء أمور محذوفين</p>
            @endif
        </div>

        <!-- الفواتير المحذوفة -->
        <div class="clinic-card p-6">
            <h2 class="mb-4 text-xl font-bold text-text">الفواتير المحذوفة ({{ $trashedInvoices->count() }})</h2>
            @if($trashedInvoices->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-surface-muted text-text-muted">
                            <tr>
                                <th class="px-4 py-3 text-xs font-medium uppercase">رقم الفاتورة</th>
                                <th class="px-4 py-3 text-xs font-medium uppercase">المريض</th>
                                <th class="px-4 py-3 text-xs font-medium uppercase">تاريخ الحذف</th>
                                <th class="px-4 py-3 text-xs font-medium uppercase">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border">
                            @foreach($trashedInvoices as $invoice)
                            <tr>
                                <td class="px-4 py-3 text-text">{{ $invoice->invoice_number }}</td>
                                <td class="px-4 py-3 text-text-muted">{{ $invoice->patient->name ?? 'محذوف' }}</td>
                                <td class="px-4 py-3 text-text-muted">{{ $invoice->deleted_at->format('Y-m-d') }}</td>
                                <td class="px-4 py-3">
                                    <form action="{{ route('trash.restore') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="type" value="invoice">
                                        <input type="hidden" name="id" value="{{ $invoice->id }}">
                                        <button type="submit" class="font-bold text-success hover:underline">استعادة</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="py-4 text-center text-text-muted">لا توجد فواتير محذوفة</p>
            @endif
        </div>

    </div>
</x-app-layout>
