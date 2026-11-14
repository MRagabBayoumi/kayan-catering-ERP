@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="الأدوار ومصفوفة الصلاحيات" :breadcrumbs="['الإدارة والنظام' => route('settings.index'), 'المستخدمون' => route('users.index'), 'الصلاحيات' => route('roles.index')]">
        <x-slot:actions>
            <a href="{{ route('users.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إضافة مستخدم جديد</span>
            </a>
            <a href="{{ route('users.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="users" size="16" />
                <span>دليل المستخدمين</span>
            </a>
            <a href="{{ route('settings.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="settings" size="16" />
                <span>إعدادات النظام</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الأدوار المعرفة:</span>
                <strong>{{ count($roles) }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    @php
        $userTabs = [
            [
                'label' => 'دليل مستخدمي النظام',
                'url' => route('users.index'),
                'icon' => 'users',
                'active' => request()->routeIs('users.index'),
            ],
            [
                'label' => 'مصفوفة الصلاحيات والأدوار (RBAC)',
                'url' => route('roles.index'),
                'icon' => 'shield',
                'active' => request()->routeIs('roles*'),
                'badge' => count($roles),
                'badge_class' => 'erp-badge-primary',
            ],
            [
                'label' => 'إعدادات المنظومة والشركة',
                'url' => route('settings.index'),
                'icon' => 'settings',
                'active' => request()->routeIs('settings*'),
            ],
        ];
    @endphp
    <x-module-nav :tabs="$userTabs" />

    <!-- Fast KPI Ribbon -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">الأدوار الوظيفية</span>
                <span class="kpi-icon text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40"><x-icon name="shield" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">{{ count($roles) }}</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">أدوار معزولة الصلاحيات</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">المستخدمين المسجلين</span>
                <span class="kpi-icon text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40"><x-icon name="users" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">{{ $totalUsers }}</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">حسابات نشطة بالمنظومة</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">الإدارات المعزولة</span>
                <span class="kpi-icon text-sky-600 bg-sky-50 dark:bg-sky-950/40"><x-icon name="layers" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">5</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">مخازن، تكاليف، مبيعات، مشتريات، مالية</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">الأمان والرقابة (RBAC)</span>
                <span class="kpi-icon text-amber-600 bg-amber-50 dark:bg-amber-950/40"><x-icon name="lock" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base font-bold text-amber-600">Strict Separation</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">عزل تام للمسؤوليات</div>
        </div>
    </div>

    <!-- Matrix Card -->
    <div class="erp-card">
        <div class="erp-card-header">
            <div class="erp-card-title">
                <x-icon name="shield" size="16" class="text-[var(--primary)]" />
                <span>مصفوفة الصلاحيات وحدود الوصول لكل دور وظيفي</span>
            </div>
        </div>
        <div class="erp-card-body p-0">
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>الدور الوظيفي</th>
                            <th>الإدارة التابع لها</th>
                            <th>المستخدمين</th>
                            <th>المخازن والتشغيلات</th>
                            <th>التكاليف والمقننات</th>
                            <th>المبيعات والمناقصات</th>
                            <th>المشتريات والموردين</th>
                            <th>المالية والتقفيل</th>
                            <th>الإدارة والصلاحيات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                            <tr>
                                <td>
                                    <div class="font-bold text-[var(--text-main)]">{{ $role['name'] }}</div>
                                    <small class="text-[var(--text-muted)] text-[11px] block max-w-xs">{{ $role['description'] }}</small>
                                </td>
                                <td>
                                    <span class="erp-badge erp-badge-neutral">{{ $role['department'] }}</span>
                                </td>
                                <td class="font-numeric text-center font-bold">
                                    {{ $role['users_count'] }}
                                </td>
                                @foreach($role['permissions'] as $perm)
                                    <td class="text-center text-xs">
                                        @if($perm === 'كامل')
                                            <span class="erp-badge erp-badge-success font-semibold">كامل</span>
                                        @elseif($perm === 'حجب')
                                            <span class="erp-badge erp-badge-danger font-semibold">محجوب</span>
                                        @elseif(str_contains($perm, 'عرض'))
                                            <span class="erp-badge erp-badge-info">{{ $perm }}</span>
                                        @else
                                            <span class="erp-badge erp-badge-warning">{{ $perm }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
