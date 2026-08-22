@props(['status'])

@php
    $classes = match ($status) {
        \App\Models\PatientServicePlan::STATUS_ACTIVE => 'border border-primary bg-primary-soft text-primary',
        \App\Models\PatientServicePlan::STATUS_COMPLETED => 'border border-success bg-success-soft text-success',
        \App\Models\PatientServicePlan::STATUS_CANCELLED => 'bg-danger-soft text-danger',
        default => 'border border-surface-border bg-surface-muted text-text-muted',
    };
    $label = \App\Models\PatientServicePlan::STATUS_LABELS[$status] ?? $status;
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {$classes}"]) }}>
    {{ $label }}
</span>
