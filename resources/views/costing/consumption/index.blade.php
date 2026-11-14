@extends('layouts.app')

@section('title', 'متابعة استهلاك المطبخ الفعلي وكشف الانحرافات')

@section('control_panel')
    <x-control-panel title="متابعة الاستهلاك الفعلي للمطبخ وانحرافات المقنن" :breadcrumbs="['إدارة التكاليف والمقننات' => route('costing.recipes.index'), 'انحرافات المقنن' => route('costing.consumption.index')]">
        <x-slot:actions>
            <a href="{{ route('costing.recipes.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="book-open" size="16" />
                <span>المقننات القياسية (BOM)</span>
            </a>
            <a href="{{ route('costing.daily-order') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="calculator" size="16" />
                <span>حساب طلبيات المطبخ</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
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
                        <th>التاريخ</th>
                        <th>المستشفى</th>
                        <th>الوجبة</th>
                        <th>الخامة</th>
                        <th>عدد الوجبات</th>
                        <th>المقنن النظري</th>
                        <th>الاستهلاك الفعلي</th>
                        <th>الانحراف (الفرق)</th>
                        <th>نسبة الهدر / التوفير</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($consumptions as $log)
                        <tr>
                            <td class="font-mono text-xs">{{ $log->log_date->format('Y-m-d') }}</td>
                            <td class="font-semibold">{{ $log->hospital->name }}</td>
                            <td>{{ $log->recipe->name }}</td>
                            <td class="font-medium text-slate-700 dark:text-slate-300">{{ $log->product->name }}</td>
                            <td class="font-mono font-bold">{{ number_format($log->actual_meal_count) }}</td>
                            <td class="font-mono text-slate-600 dark:text-slate-400">
                                {{ number_format($log->standard_expected_quantity, 2) }} {{ $log->unit }}
                            </td>
                            <td class="font-mono font-bold">
                                {{ number_format($log->actual_used_quantity, 2) }} {{ $log->unit }}
                            </td>
                            <td class="font-mono font-bold {{ $log->variance_quantity > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $log->variance_quantity > 0 ? '+' : '' }}{{ number_format($log->variance_quantity, 2) }} {{ $log->unit }}
                            </td>
                            <td>
                                @if($log->variance_percentage > 5)
                                    <span class="erp-badge erp-badge-danger">هدر {{ $log->variance_percentage }}%</span>
                                @elseif($log->variance_percentage < -5)
                                    <span class="erp-badge erp-badge-warning">توفير حاد {{ abs($log->variance_percentage) }}%</span>
                                @else
                                    <span class="erp-badge erp-badge-success">ضمن المقنن ({{ $log->variance_percentage }}%)</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-8 text-slate-400">
                                لا توجد سجلات استهلاك مطبخ مسجلة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $consumptions->links() }}
        </div>
    </div>
</div>
@endsection
