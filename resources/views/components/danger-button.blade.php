<button {{ $attributes->merge(['type' => 'submit', 'class' => 'clinic-btn-danger-soft']) }}>
    {{ $slot }}
</button>
