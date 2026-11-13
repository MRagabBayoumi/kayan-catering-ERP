@extends('layouts.app')

@section('title', 'المناقصات والعطاءات (Tenders)')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">سجل المناقصات والعطاءات (إدارة المبيعات)</h1>
            <p class="text-xs text-slate-500 mt-1">متابعة كراسات الشروط، موعد فتح المظاريف، التأمين الابتدائي، وحالة الترسية</p>
        </div>
        <a href="{{ route('sales.tenders.create') }}" class="erp-btn erp-btn-primary">
            <x-icon name="plus" size="16" />
            <span>تسجيل مناقصة جديدة</span>
        </a>
    </div>

    @if(session('success'))
        <div class="erp-alert erp-alert-success">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="erp-card">
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>رقم المناقصة</th>
                        <th>اسم المناقصة والجهة</th>
                        <th>تاريخ الإعلان</th>
                        <th>موعد فتح المظاريف</th>
                        <th>التأمين الابتدائي</th>
                        <th>كراسة الشروط</th>
                        <th>حالة المناقصة</th>
                        <th>أسعار المنافسين المؤرشفة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenders as $tender)
                        <tr>
                            <td class="font-mono font-bold text-primary">{{ $tender->tender_number }}</td>
                            <td>
                                <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $tender->title }}</div>
                                <div class="text-xs text-slate-500">{{ $tender->entity_name }}</div>
                            </td>
                            <td class="font-mono text-xs">{{ $tender->announcement_date?->format('Y-m-d') ?? '-' }}</td>
                            <td class="font-mono text-xs font-bold text-amber-600 dark:text-amber-400">
                                {{ $tender->envelope_opening_date?->format('Y-m-d') ?? 'غير محدد' }}
                            </td>
                            <td class="font-mono">{{ number_format($tender->bid_bond_amount, 2) }} ج.م</td>
                            <td class="font-mono">{{ number_format($tender->booklet_cost, 2) }} ج.م</td>
                            <td>
                                @if($tender->status === 'awarded')
                                    <span class="erp-badge erp-badge-success">تمت الترسية علينا</span>
                                @elseif($tender->status === 'under_study')
                                    <span class="erp-badge erp-badge-info">تحت الدراسة</span>
                                @elseif($tender->status === 'submitted')
                                    <span class="erp-badge erp-badge-warning">تم تقديم العطاء</span>
                                @else
                                    <span class="erp-badge erp-badge-danger">{{ $tender->status }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="erp-badge erp-badge-secondary">
                                    {{ $tender->competitorPrices->count() }} عروض منافسين
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                لا توجد مناقصات مسجلة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $tenders->links() }}
        </div>
    </div>
</div>
@endsection
