@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center rounded-lg bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-blue-900/30 dark:text-blue-200 dark:ring-blue-800'
            : 'flex items-center rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-gray-300 dark:hover:bg-gray-700/60 dark:hover:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
