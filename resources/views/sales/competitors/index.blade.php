@extends('layouts.app')

@section('title', 'أرشيف أسعار المنافسين للعام القادم')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">أرشيف أسعار المنافسين في المناقصات (العام القادم)</h1>
            <p class="text-xs text-slate-500 mt-1">حصر وتخزين أسعار الشركات المنافسة في كل وجبة ومناقصة لبناء استراتيجية تسعير دقيقة للعام القادم</p>
        </div>
    </div>

    @if(session('success'))
        <div class="erp-alert erp-alert-success">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Add Competitor Price Modal or Form -->
    <div class="erp-card p-6">
        <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-4">أرشفة سعر منافس جديد</h3>
        <form action="{{ route('sales.competitors.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="erp-form-label">المناقصة</label>
                <select name="tender_id" class="erp-form-control" required>
                    <option value="">-- اختر المناقصة --</option>
                    @foreach($tenders as $tnd)
                        <option value="{{ $tnd->id }}">{{ $tnd->tender_number }} - {{ $tnd->entity_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-form-label">اسم الشركة المنافسة</label>
                <input type="text" name="competitor_name" class="erp-form-control" placeholder="مثال: الشركة الدولية للخدمات" required>
            </div>
            <div>
                <label class="erp-form-label">اسم البند / الوجبة</label>
                <input type="text" name="item_or_meal_name" class="erp-form-control" placeholder="مثال: وجبة غداء كاملة باللحم" required>
            </div>
            <div>
                <label class="erp-form-label">سعرنا المقدم (ج.م)</label>
                <input type="number" step="0.01" name="our_bid_price" class="erp-form-control" placeholder="مثلاً: 85.00">
            </div>
            <div>
                <label class="erp-form-label">سعر المنافس المقدم (ج.م)</label>
                <input type="number" step="0.01" name="competitor_price" class="erp-form-control" placeholder="مثلاً: 92.00" required>
            </div>
            <div>
                <label class="erp-form-label">سنة المناقصة</label>
                <input type="number" name="tender_year" class="erp-form-control" value="{{ date('Y') }}" required>
            </div>
            <div class="md:col-span-2 flex items-end">
                <button type="submit" class="erp-btn erp-btn-primary w-full">
                    <x-icon name="plus" size="16" />
                    <span>حفظ وأرشفة السعر</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Archive Table -->
    <div class="erp-card">
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>سنة المناقصة</th>
                        <th>المناقصة والجهة</th>
                        <th>الشركة المنافسة</th>
                        <th>البند / الوجبة</th>
                        <th>سعرنا المقدم</th>
                        <th>سعر المنافس</th>
                        <th>نسبة الفرق</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($competitorPrices as $cp)
                        <tr>
                            <td class="font-mono font-bold text-primary">{{ $cp->tender_year }}</td>
                            <td>
                                <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $cp->tender->title }}</div>
                                <div class="text-xs text-slate-500">{{ $cp->tender->entity_name }}</div>
                            </td>
                            <td class="font-bold text-slate-700 dark:text-slate-300">{{ $cp->competitor_name }}</td>
                            <td>{{ $cp->item_or_meal_name }}</td>
                            <td class="font-mono">{{ $cp->our_bid_price ? number_format($cp->our_bid_price, 2).' ج.م' : '-' }}</td>
                            <td class="font-mono font-bold text-amber-600 dark:text-amber-400">
                                {{ number_format($cp->competitor_price, 2) }} ج.م
                            </td>
                            <td class="font-mono text-xs">
                                @if($cp->variance_percentage !== null)
                                    <span class="{{ $cp->variance_percentage > 0 ? 'text-rose-600 font-bold' : 'text-emerald-600 font-bold' }}">
                                        {{ $cp->variance_percentage > 0 ? '+' : '' }}{{ $cp->variance_percentage }}%
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-xs text-slate-500">{{ $cp->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                لا توجد أسعار منافسين مؤرشفة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $competitorPrices->links() }}
        </div>
    </div>
</div>
@endsection
