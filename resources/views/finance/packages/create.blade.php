<x-app-layout>
    <x-slot name="title">بيع باقة جلسات</x-slot>

    <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-gray-200">بيع باقة جلسات لمريض</h2>

        <form action="{{ route('packages.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المريض *</label>
                    <select name="patient_id" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                        <option value="">اختر المريض...</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اسم الباقة (مثال: باقة ٨ جلسات) *</label>
                    <input type="text" name="name" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">عدد الجلسات *</label>
                        <input type="number" name="total_sessions" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المدفوع (ر.س) *</label>
                        <input type="number" name="price_paid" step="0.01" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <a href="{{ route('packages.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg ml-3">إلغاء</a>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg">تأكيد البيع</button>
            </div>
        </form>
    </div>
</x-app-layout>
