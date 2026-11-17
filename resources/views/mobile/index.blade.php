@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="بوابة العمليات الميدانية المتنقلة (Mobile Operations)" :breadcrumbs="['الرئيسية' => route('home'), 'بوابة الميدان' => route('mobile.index')]">
        <x-slot:actions>
            <a href="{{ route('warehouse.dispatches') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="truck" size="16" />
                <span>أذون الصرف الرئيسية</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-5" dir="rtl">
    <!-- User Welcome Banner -->
    <div class="erp-card p-5 bg-gradient-to-l from-sky-600 to-slate-800 text-white rounded-xl shadow-md">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center text-xl font-bold">
                {{ mb_substr($user->name, 0, 1) }}
            </div>
            <div>
                <h3 class="font-bold text-lg text-white">{{ $user->name }}</h3>
                <span class="text-xs text-sky-200 block">الدور: {{ $user->role_name ?? 'العمليات الميدانية' }}</span>
            </div>
        </div>
    </div>

    <!-- Quick Action Cards (Large touch targets for mobile) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="{{ route('mobile.driver.trips') }}" class="erp-card p-5 hover:border-sky-500 hover:shadow-md transition-all flex flex-col justify-between block group">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center">
                    <x-icon name="truck" size="22" />
                </div>
                <span class="erp-badge erp-badge-primary font-numeric text-xs">{{ $activeDispatchesCount }} جارية</span>
            </div>
            <div class="mt-4">
                <h4 class="font-bold text-slate-800 group-hover:text-sky-600 transition-colors">رحلات التوريد وعهدة السائق</h4>
                <p class="text-xs text-slate-500 mt-1">متابعة الأذون المحملة بعهدة السيارة وتسليمها للمستشفيات</p>
            </div>
        </a>

        <a href="{{ route('warehouse.dispatches') }}" class="erp-card p-5 hover:border-emerald-500 hover:shadow-md transition-all flex flex-col justify-between block group">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                    <x-icon name="clipboard-check" size="22" />
                </div>
                <span class="erp-badge erp-badge-success font-numeric text-xs">{{ $pendingReceivingCount }} بانتظار الاستلام</span>
            </div>
            <div class="mt-4">
                <h4 class="font-bold text-slate-800 group-hover:text-emerald-600 transition-colors">فحص واستلام التوريد بالموقع</h4>
                <p class="text-xs text-slate-500 mt-1">مطابقة الأصناف الواردة بالمستشفى وتأكيد إشعار التسليم POD</p>
            </div>
        </a>

        <a href="{{ route('site.kitchen-issues.index') }}" class="erp-card p-5 hover:border-amber-500 hover:shadow-md transition-all flex flex-col justify-between block group">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
                    <x-icon name="chef-hat" size="22" />
                </div>
                <span class="erp-badge erp-badge-warning text-xs">مطبخ الموقع</span>
            </div>
            <div class="mt-4">
                <h4 class="font-bold text-slate-800 group-hover:text-amber-600 transition-colors">صرف خامات المطبخ الفرعي</h4>
                <p class="text-xs text-slate-500 mt-1">تسجيل أذون صرف الخامات من مخزن الموقع لإعداد الوجبات</p>
            </div>
        </a>

        <a href="{{ route('site.census.index') }}" class="erp-card p-5 hover:border-purple-500 hover:shadow-md transition-all flex flex-col justify-between block group">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center">
                    <x-icon name="users" size="22" />
                </div>
                <span class="erp-badge erp-badge-neutral text-xs">حصر النزلاء</span>
            </div>
            <div class="mt-4">
                <h4 class="font-bold text-slate-800 group-hover:text-purple-600 transition-colors">سجل التعداد والنزلاء اليومي</h4>
                <p class="text-xs text-slate-500 mt-1">إدخال حصر مرضى المستشفى حسب الوجبات والحميات الغذائية</p>
            </div>
        </a>
    </div>
</div>
@endsection
