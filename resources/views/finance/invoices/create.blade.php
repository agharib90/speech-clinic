<x-app-layout>
    <x-slot name="title">إنشاء فاتورة جديدة</x-slot>

    <div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-gray-200">إنشاء فاتورة جديدة</h2>
                <!-- كود عرض الأخطاء اللي هنضيفه -->
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                <h3 class="text-red-800 font-bold mb-2">يوجد أخطاء في البيانات:</h3>
                <ul class="list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <!-- نهاية كود الأخطاء -->

        @include('finance.invoices._form', [
            'workspaceMode' => (bool) $fromWorkspace,
            'embeddedWorkspace' => false,
            'patient' => $workspacePatient,
            'planItems' => $planItems,
            'formAction' => $fromWorkspace
                ? URL::signedRoute('invoices.store', ['workspace_patient' => $selectedPatientId])
                : route('invoices.store'),
        ])
    </div>
</x-app-layout>
