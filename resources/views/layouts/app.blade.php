<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $pageTitle = trim((string) ($title ?? 'لوحة التحكم'));
        $clinicName = ($settings ?? null)?->clinic_name ?: 'عيادة التخاطب';
    @endphp
    <title>{{ $pageTitle }} - {{ $clinicName }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans-arabic:400,500,600,700|inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="font-sans antialiased bg-surface text-text transition-colors duration-300">
    @php
        $currentRole = Auth::user()->roles->pluck('name')->first() ?? 'بدون دور';
    @endphp

    <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">
        <div
            x-cloak
            x-show="sidebarOpen"
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-surface-overlay lg:hidden">
        </div>

        <div
            class="fixed inset-y-0 right-0 z-40 w-72 transform transition duration-200 ease-out lg:static lg:w-64 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
            @keydown.escape.window="sidebarOpen = false">
            @include('partials.sidebar')
        </div>

        <div class="flex min-w-0 flex-1 flex-col">
            <nav class="sticky top-0 z-20 border-b border-surface-border bg-surface-elevated px-4 py-3 backdrop-blur sm:px-6">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            @click="sidebarOpen = true"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-surface-border text-text-muted transition hover:bg-surface-muted hover:text-text focus:outline-none focus:ring-2 focus:ring-primary lg:hidden"
                            aria-label="فتح القائمة">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div class="min-w-0">
                            <p class="truncate text-lg font-bold text-text">{{ $pageTitle }}</p>
                            <p class="mt-0.5 hidden text-xs text-text-muted sm:block">{{ $clinicName }} · نظام إدارة العيادة</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3">
                        <button
                            type="button"
                            onclick="document.documentElement.classList.toggle('dark')"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-surface-border text-text-muted transition hover:bg-surface-muted hover:text-text focus:outline-none focus:ring-2 focus:ring-primary"
                            aria-label="تبديل الوضع الليلي">
                            <svg class="h-5 w-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <svg class="hidden h-5 w-5 dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </button>

                        <div class="relative" x-data="{ open: false }">
                            <button
                                type="button"
                                @click="open = !open"
                                class="flex items-center gap-2 rounded-lg border border-surface-border bg-surface-elevated px-2.5 py-2 text-sm text-text transition hover:bg-surface-muted focus:outline-none focus:ring-2 focus:ring-primary">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-soft text-sm font-bold text-primary">
                                    {{ mb_substr(Auth::user()->name, 0, 1) }}
                                </span>
                                <span class="hidden text-right sm:block">
                                    <span class="block max-w-36 truncate font-medium">{{ Auth::user()->name }}</span>
                                    <span class="block max-w-36 truncate text-xs text-text-muted">{{ $currentRole }}</span>
                                </span>
                                <svg class="h-4 w-4 text-text-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div
                                x-cloak
                                x-show="open"
                                @click.away="open = false"
                                x-transition
                                class="absolute left-0 mt-2 w-56 overflow-hidden rounded-lg border border-surface-border bg-surface-elevated py-1 shadow-lg">
                                <div class="border-b border-surface-border px-4 py-3 sm:hidden">
                                    <p class="truncate text-sm font-semibold text-text">{{ Auth::user()->name }}</p>
                                    <p class="truncate text-xs text-text-muted">{{ $currentRole }}</p>
                                </div>
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-text-muted hover:bg-surface-muted hover:text-text">
                                    الملف الشخصي
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-right text-sm text-text-muted hover:bg-surface-muted hover:text-text">
                                        تسجيل الخروج
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
