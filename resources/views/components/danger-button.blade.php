<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-md border border-transparent bg-danger px-4 py-2 text-xs font-semibold uppercase tracking-widest text-danger-contrast transition duration-150 ease-in-out hover:bg-danger-hover active:bg-danger-hover focus:outline-none focus:ring-2 focus:ring-danger focus:ring-offset-2 focus:ring-offset-surface']) }}>
    {{ $slot }}
</button>
