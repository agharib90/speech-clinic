@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'clinic-field']) }}>
