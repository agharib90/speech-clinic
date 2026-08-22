<x-app-layout>
    <x-slot name="title">{{ isset($patientServicePlan) ? 'تعديل خطة الخدمات' : 'خطة خدمات جديدة' }}</x-slot>

    @include('patients.service-plans._form')
</x-app-layout>
