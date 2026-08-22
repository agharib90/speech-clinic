<button {{ $attributes->merge(['type' => 'button', 'class' => 'clinic-btn-secondary disabled:opacity-40']) }}>
    {{ $slot }}
</button>
