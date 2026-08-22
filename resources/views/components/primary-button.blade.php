<button {{ $attributes->merge(['type' => 'submit', 'class' => 'clinic-btn-primary']) }}>
    {{ $slot }}
</button>
