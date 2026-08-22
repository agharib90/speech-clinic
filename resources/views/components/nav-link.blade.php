@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex min-h-11 items-center rounded-[10px] bg-primary-soft font-semibold text-primary ring-1 ring-primary-soft focus:outline-none focus:ring-2 focus:ring-primary'
            : 'flex min-h-11 items-center rounded-[10px] text-text-muted transition duration-150 hover:bg-primary-soft hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
