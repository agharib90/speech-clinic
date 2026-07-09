@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center rounded-lg bg-primary-soft text-primary shadow-sm ring-1 ring-primary-soft focus:outline-none focus:ring-2 focus:ring-primary'
            : 'flex items-center rounded-lg text-text-muted hover:bg-surface-muted hover:text-text focus:outline-none focus:ring-2 focus:ring-primary';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
