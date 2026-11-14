@extends('layouts.app')

@section('title', 'المستشفيات والجهات المتعاقدة')

@section('control_panel')
    <x-control-panel title="المستشفيات والجهات المتعاقدة (إدارة المبيعات)" :breadcrumbs="['المبيعات والمناقصات' => route('sales.hospitals.index'), 'المستشفيات' => route('sales.hospitals.index')]">
        <x-slot:actions>
            <a href="{{ route('sales.hospitals.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إضافة تعاقد مستشفى جديد</span>
            </a>
            <a href="{{ route('sales.tenders.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="file-text" size="16" />
                <span>سجل المناقصات</span>
            </a>
            <a href="{{ route('sales.competitors.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="archive" size="16" />
                <span>أرشيف أسعار المنافسين</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي المستشفيات:</span>
                <strong>{{ count($hospitals) }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    @php
        $salesTabs = [
            [
                'label' => 'المستشفيات والجهات المتعاقدة',
                'url' => route('sales.hospitals.index'),
                'icon' => 'building-2',
                'active' => request()->routeIs('sales.hospitals*'),
                'badge' => count($hospitals),
                'badge_class' => 'erp-badge-primary',
            ],
            [
                'label' => 'المناقصات والعطاءات (Tenders)',
                'url' => route('sales.tenders.index'),
                'icon' => 'file-text',
                'active' => request()->routeIs('sales.tenders*'),
            ],
            [
                'label' => 'أرشيف أسعار المنافسين',
                'url' => route('sales.competitors.index'),
                'icon' => 'archive',
                'active' => request()->routeIs('sales.competitors*'),
            ],
        ];
    @endphp
    <x-module-nav :tabs="$salesTabs" />

    <!-- Fast Status KPI Ribbon (Sales & Hospitals Overview) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">المستشفيات والجهات المتعاقدة</span>
                <span class="kpi-icon text-cyan-600 bg-cyan-50 dark:bg-cyan-950/40"><x-icon name="building-2" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">{{ $hospitals->total() }}</div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">عقود توريد أغذية معتمدة</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">الطاقة الاستيعابية اليومية</span>
                <span class="kpi-icon text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40"><x-icon name="users" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">
                {{ number_format($hospitals->sum('daily_patient_capacity')) }} <span class="text-xs font-normal text-[var(--text-muted)]">نزيل/يوم</span>
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">إجمالي وجبات المرضى اليومية</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">المستشفيات النشطة حالياً</span>
                <span class="kpi-icon text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40"><x-icon name="check-circle" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">
                {{ $hospitals->where('is_active', true)->count() }}
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">مواقع توريد جارية ومنتظمة</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header mb-1">
                <span class="kpi-label">المستودعات الفرعية المربوطة</span>
                <span class="kpi-icon text-amber-600 bg-amber-50 dark:bg-amber-950/40"><x-icon name="warehouse" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-2xl font-black text-[var(--text-main)]">
                {{ $hospitals->whereNotNull('site_warehouse_id')->count() }}
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">مخازن فرعية داخل مواقع المستشفيات</div>
        </div>
    </div>

    @if(session('success'))
        <div class="erp-alert erp-alert-success">
            <x-icon name="check" size="16" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="erp-card">
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>كود المستشفى</th>
                        <th>اسم المستشفى</th>
                        <th>رقم العقد</th>
                        <th>السعة السريرية</th>
                        <th>أسعار الوجبات (فطور / غداء / عشاء)</th>
                        <th>مخزن الموقع الفرعي</th>
                        <th>حالة التعاقد</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($hospitals as $hospital)
                        <tr>
                            <td class="font-mono font-bold text-primary">{{ $hospital->code }}</td>
                            <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $hospital->name }}</td>
                            <td class="font-mono text-xs text-slate-500">{{ $hospital->contract_number }}</td>
                            <td class="font-mono font-bold">{{ number_format($hospital->daily_patient_capacity) }} نزيل</td>
                            <td class="font-mono text-xs">
                                <span class="text-slate-600 dark:text-slate-400">{{ number_format($hospital->meal_price_breakfast, 1) }}</span> /
                                <span class="text-primary font-bold">{{ number_format($hospital->meal_price_lunch, 1) }}</span> /
                                <span class="text-slate-600 dark:text-slate-400">{{ number_format($hospital->meal_price_dinner, 1) }} ج.م</span>
                            </td>
                            <td>
                                @if($hospital->siteWarehouse)
                                    <span class="erp-badge erp-badge-info">{{ $hospital->siteWarehouse->name }}</span>
                                @else
                                    <span class="erp-badge erp-badge-warning">غير مؤسس</span>
                                @endif
                            </td>
                            <td>
                                @if($hospital->contract_status === 'active')
                                    <span class="erp-badge erp-badge-success">ساري ومفعل</span>
                                @elseif($hospital->contract_status === 'terminated')
                                    <span class="erp-badge erp-badge-danger">تم إنهاء التعاقد</span>
                                @else
                                    <span class="erp-badge erp-badge-warning">{{ $hospital->contract_status }}</span>
                                @endif
                            </td>
                            <td>
                                @if($hospital->contract_status === 'active')
                                    <form action="{{ route('sales.hospitals.terminate', $hospital->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        <input type="hidden" name="reason" value="إنهاء تعاقد من إدارة المبيعات">
                                        <button type="submit" class="erp-btn erp-btn-danger erp-btn-sm" title="إنهاء التعاقد">
                                            <span>إنهاء التعاقد</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">مؤرشف</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                لا توجد مستشفيات مسجلة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $hospitals->links() }}
        </div>
    </div>
</div>
@endsection
