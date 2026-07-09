@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full border-l-4 border-primary bg-primary-soft py-2 pe-4 ps-3 text-start text-base font-medium text-primary transition duration-150 ease-in-out focus:border-primary focus:bg-primary-soft focus:outline-none focus:text-primary'
            : 'block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-text-muted transition duration-150 ease-in-out hover:border-surface-border hover:bg-surface-muted hover:text-text focus:border-surface-border focus:bg-surface-muted focus:outline-none focus:text-text';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
