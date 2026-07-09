<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-md border border-transparent bg-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-primary-contrast transition duration-150 ease-in-out hover:bg-primary-hover focus:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-surface active:bg-primary-hover']) }}>
    {{ $slot }}
</button>
