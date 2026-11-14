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
