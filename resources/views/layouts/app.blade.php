<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light" data-density="comfortable">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'الرئيسية' }} | {{ $globalCompanySetting?->company_name ?? config('app.name', 'Kayan ERP') }}</title>

    @if(!empty($globalCompanySetting?->logo_url))
        <link rel="apple-touch-icon" href="{{ $globalCompanySetting->logo_url }}">
    @endif
    <!-- PWA & Desktop App Settings -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="كيان ERP">
    <meta name="turbo-cache-control" content="no-cache">
    <link rel="apple-touch-icon" href="{{ asset('icon-192.png') }}">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">

    @vite(['resources/js/theme-init.js', 'resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="erp-app-wrapper">
    <!-- Dynamic Module Name Resolver -->
    @php
        $activeModuleName = $moduleName ?? (trim($__env->yieldContent('module_name')) ?: null);
        if (!$activeModuleName) {
            if (request()->is('warehouse*')) {
                $activeModuleName = 'إدارة المخازن والـ FIFO';
            } elseif (request()->is('costing*')) {
                $activeModuleName = 'إدارة التكاليف والمقننات';
            } elseif (request()->is('sales*')) {
                $activeModuleName = 'المبيعات والمناقصات';
            } elseif (request()->is('procurement*')) {
                $activeModuleName = 'إدارة المشتريات والتوريدات';
            } elseif (request()->is('finance*')) {
                $activeModuleName = 'إدارة الحسابات والمالية';
            } elseif (request()->is('settings*')) {
                $activeModuleName = 'إعدادات النظام';
            } elseif (request()->is('users*') || request()->is('roles*')) {
                $activeModuleName = 'المستخدمين والصلاحيات';
            } else {
                $activeModuleName = 'لوحة التحكم';
            }
        }
    @endphp

    <!-- Top Navigation Bar -->
    <x-topbar :module-name="$activeModuleName" />

    <!-- Application Body (Sidebar + Main View) -->
    <div class="erp-body-layout">
        <!-- Sidebar Navigation -->
        <x-sidebar />
        <div id="sidebarOverlay" class="erp-sidebar-overlay" aria-hidden="true"></div>

        <!-- Main Work Area -->
        <main class="erp-main">
            @yield('control_panel')

            <div class="erp-content">
                @yield('content')
            </div>

            <!-- Footer -->
            <footer class="erp-footer">
                <div><a href="#">Kayan ERP</a> © {{ date('Y') }}</div>
                <span class="erp-footer-sep">·</span>
                <div class="font-numeric">الإصدار 1.0</div>
            </footer>
        </main>
    </div>

    <!-- Kayan AI Copilot -->
    <x-ai-copilot />
</div>

@stack('scripts')

</body>
</html>
