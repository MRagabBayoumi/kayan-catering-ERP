@extends('layouts.app')

@section('title', 'حركة سيارات التوزيع وتوزيع مصاريف الوقود')

@section('control_panel')
    <x-control-panel title="حركة سيارات المخزن وتوزيع مصاريف النقل والوقود" :breadcrumbs="['إدارة المخازن' => route('warehouse.index'), 'حركة السيارات' => route('warehouse.trips.index')]">
        <x-slot:actions>
            <a href="{{ route('warehouse.trips.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>تسجيل رحلة وتفويل سيارة</span>
            </a>
            <a href="{{ route('warehouse.dispatches') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="truck" size="16" />
                <span>أذون الصرف للسيارات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الرحلات:</span>
                <strong>{{ $trips->total() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    @if(session('success'))
        <div class="erp-alert erp-alert-success">
            <x-icon name="check" size="16" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Monthly KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="erp-card p-5">
            <span class="text-xs text-slate-500 font-medium">إجمالي مصاريف وقود هذا الشهر</span>
            <div class="flex items-center justify-between mt-2">
                <h3 class="text-2xl font-bold font-mono text-slate-800 dark:text-slate-100">
                    {{ number_format($totalFuelThisMonth, 2) }} <span class="text-xs text-slate-500 font-normal">ج.م</span>
                </h3>
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                    <x-icon name="zap" size="20" />
                </div>
            </div>
            <p class="text-xs text-slate-400 mt-2">تم توزيعها آلياً على مراكز تكلفة المستشفيات</p>
        </div>

        <div class="erp-card p-5">
            <span class="text-xs text-slate-500 font-medium">عدد رحلات التوزيع المسجلة</span>
            <div class="flex items-center justify-between mt-2">
                <h3 class="text-2xl font-bold font-mono text-primary">
                    {{ number_format($totalTripsThisMonth) }} <span class="text-xs text-slate-500 font-normal">نقلة</span>
                </h3>
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <x-icon name="truck" size="20" />
                </div>
            </div>
            <p class="text-xs text-slate-400 mt-2">موزعة على كافة خطوط سير المستشفيات</p>
        </div>

        <div class="erp-card p-5">
            <span class="text-xs text-slate-500 font-medium">آلية التوزيع والتسوية</span>
            <div class="flex items-center justify-between mt-2">
                <span class="erp-badge erp-badge-success text-xs">تصفية عهدة المخزن آلية</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                    <x-icon name="check-circle" size="20" />
                </div>
            </div>
            <p class="text-xs text-slate-400 mt-2">تحميل مباشر لنصيب كل مستشفى في تقرير الأرباح</p>
        </div>
    </div>

    <!-- Table of Trips -->
    <div class="erp-card">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">سجل تحركات سيارات التوزيع وتوزيع التكاليف</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>رقم النقلة</th>
                        <th>لوحة السيارة</th>
                        <th>السائق</th>
                        <th>تاريخ الرحلة</th>
                        <th>مصروف الوقود</th>
                        <th>إجمالي المصروف</th>
                        <th>المستشفيات المستفيدة</th>
                        <th>نصيب الموقع المحمل</th>
                        <th>مسجل الرحلة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trips as $trip)
                        <tr>
                            <td class="font-mono text-xs font-bold text-primary">{{ $trip->trip_number }}</td>
                            <td class="font-bold text-slate-800 dark:text-slate-100">{{ $trip->vehicle_plate }}</td>
                            <td class="text-xs text-slate-700 dark:text-slate-300">{{ $trip->driver?->name ?? 'مندوب توزيع' }}</td>
                            <td class="font-mono text-xs">{{ $trip->trip_date->format('Y-m-d') }}</td>
                            <td class="font-mono font-bold text-amber-600">
                                {{ number_format($trip->fuel_expense, 2) }} ج.م
                            </td>
                            <td class="font-mono font-bold text-slate-900 dark:text-slate-100">
                                {{ number_format($trip->total_trip_expense, 2) }} ج.م
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($trip->destinations as $dest)
                                        <span class="erp-badge erp-badge-neutral text-xs">
                                            {{ $dest->hospital?->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="font-mono font-bold text-emerald-600">
                                @if($trip->destinations->isNotEmpty())
                                    {{ number_format($trip->destinations->first()->allocated_amount, 2) }} ج.م / موقع
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-xs text-slate-400">{{ $trip->creator?->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-8 text-slate-400">
                                لا توجد رحلات سيارات مسجلة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $trips->links() }}
        </div>
    </div>
</div>
@endsection
