<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $clinicName = ($settings ?? null)?->clinic_name ?: 'عيادة التخاطب';
            $logoPath = ($settings ?? null)?->logo_path;
            $logoUrl = $logoPath ? \Illuminate\Support\Facades\Storage::url($logoPath) : null;
        @endphp

        <title>{{ $clinicName }}</title>

        @include('partials.theme-init')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=ibm-plex-sans-arabic:400,500,600,700|inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-text antialiased bg-surface">
        <div class="min-h-screen flex flex-col items-center justify-center px-4 py-8">
            <div class="w-full max-w-md">
                <a href="/" class="mx-auto flex w-fit flex-col items-center gap-3">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="شعار {{ $clinicName }}" class="h-20 w-20 rounded-lg border border-surface-border bg-surface-elevated object-contain p-2 shadow-sm">
                    @else
                        <span class="flex h-20 w-20 items-center justify-center rounded-lg border border-surface-border bg-surface-elevated shadow-sm" aria-hidden="true">
                            <span class="clinic-soundwave">
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>
                        </span>
                    @endif
                    <span class="text-center text-xl font-bold text-text">{{ $clinicName }}</span>
                </a>

                <div class="clinic-card mt-6 overflow-hidden px-6 py-5">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
