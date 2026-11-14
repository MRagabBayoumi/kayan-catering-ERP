@extends('layouts.app')

@section('module_name', 'الرئيسية')

@section('content')
<div data-page="home-apps" class="odoo-launcher-container" dir="rtl">
    <!-- Subtle Minimal App Search -->
    <div class="odoo-launcher-search">
        <input type="search" id="homeAppSearch" class="odoo-search-input" placeholder="ابحث في التطبيقات..." autocomplete="off">
        <span class="odoo-search-icon">
            <x-icon name="search" size="18" />
        </span>
    </div>

    <!-- Odoo Desktop Apps Launcher Grid -->
    <div class="odoo-apps-grid" id="appsHubGrid">
        @can('manage-warehouse')
        <a href="{{ route('warehouse.index') }}" class="odoo-app-item" data-app-name="المخازن والتشغيلات">
            <div class="odoo-app-icon odoo-icon-blue">
                <x-icon name="warehouse" size="36" />
            </div>
            <span class="odoo-app-label">المخازن</span>
        </a>
        @endcan

        @can('manage-costing')
        <a href="{{ route('costing.recipes.index') }}" class="odoo-app-item" data-app-name="المقننات والتكاليف">
            <div class="odoo-app-icon odoo-icon-emerald">
                <x-icon name="calculator" size="36" />
            </div>
            <span class="odoo-app-label">المقننات والتكاليف</span>
        </a>
        @endcan

        @can('manage-sales')
        <a href="{{ route('sales.hospitals.index') }}" class="odoo-app-item" data-app-name="المستشفيات والتعاقدات">
            <div class="odoo-app-icon odoo-icon-cyan">
                <x-icon name="building-2" size="36" />
            </div>
            <span class="odoo-app-label">المستشفيات</span>
        </a>

        <a href="{{ route('sales.tenders.index') }}" class="odoo-app-item" data-app-name="المناقصات والعطاءات">
            <div class="odoo-app-icon odoo-icon-amber">
                <x-icon name="file-text" size="36" />
            </div>
            <span class="odoo-app-label">المناقصات</span>
        </a>
        @endcan

        @can('manage-procurement')
        <a href="{{ route('procurement.orders.index') }}" class="odoo-app-item" data-app-name="المشتريات والتوريدات">
            <div class="odoo-app-icon odoo-icon-teal">
                <x-icon name="shopping-bag" size="36" />
            </div>
            <span class="odoo-app-label">المشتريات</span>
        </a>
        @endcan

        @can('manage-finance')
        <a href="{{ route('finance.suppliers.index') }}" class="odoo-app-item" data-app-name="الحسابات والمالية">
            <div class="odoo-app-icon odoo-icon-purple">
                <x-icon name="wallet" size="36" />
            </div>
            <span class="odoo-app-label">الحسابات والمالية</span>
        </a>
        @endcan

        @can('manage-closing')
        <a href="{{ route('finance.closing.index') }}" class="odoo-app-item" data-app-name="التقفيل واليومية للمالك">
            <div class="odoo-app-icon odoo-icon-indigo">
                <x-icon name="pie-chart" size="36" />
            </div>
            <span class="odoo-app-label">التقفيل للمالك</span>
        </a>
        @endcan

        @can('manage-dashboard')
        <a href="{{ route('dashboard') }}" class="odoo-app-item" data-app-name="لوحة المؤشرات والرقابة">
            <div class="odoo-app-icon odoo-icon-violet">
                <x-icon name="layout-dashboard" size="36" />
            </div>
            <span class="odoo-app-label">المؤشرات العامة</span>
        </a>
        @endcan

        @can('use-ai-copilot')
        <button type="button" class="odoo-app-item" data-action="open-ai-copilot" data-app-name="مساعد كيان الذكي">
            <div class="odoo-app-icon odoo-icon-rose">
                <x-icon name="sparkles" size="36" />
            </div>
            <span class="odoo-app-label">مساعد كيان (AI)</span>
        </button>
        @endcan

        @can('manage-users')
        <a href="{{ route('users.index') }}" class="odoo-app-item" data-app-name="المستخدمين ومصفوفة الصلاحيات">
            <div class="odoo-app-icon odoo-icon-slate">
                <x-icon name="users" size="36" />
            </div>
            <span class="odoo-app-label">المستخدمين</span>
        </a>
        @endcan

        @can('manage-company-settings')
        <a href="{{ route('settings.index') }}" class="odoo-app-item" data-app-name="إعدادات المنظومة">
            <div class="odoo-app-icon odoo-icon-gray">
                <x-icon name="settings" size="36" />
            </div>
            <span class="odoo-app-label">إعدادات النظام</span>
        </a>
        @endcan
    </div>

    <!-- Empty Search State -->
    <div id="appsNoResults" class="erp-card p-6 text-center mt-6 hidden">
        <p class="text-xs text-slate-500">لا توجد تطبيقات مطابقة لبحثك</p>
    </div>
</div>
@endsection
