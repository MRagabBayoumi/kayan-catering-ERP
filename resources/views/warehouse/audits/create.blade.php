@extends('layouts.app')

@section('content')
<div class="p-6 max-w-4xl mx-auto space-y-6" dir="rtl">
    <!-- Header -->
    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>📝</span>
                <span>تسجيل جلسة جرد أعمى للمخزن</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                خاص بأمين المخزن: قم بعد الأصناف فعلياً على الرف وإدخال الكمية الظاهرة أمامك دون الرجوع لأي دفاتر.
            </p>
        </div>
        <a href="{{ route('warehouse.audits.index') }}" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl transition">
            رجوع للقائمة
        </a>
    </div>

    <form action="{{ route('warehouse.audits.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">المخزن المراد جرده *</label>
                <select name="warehouse_id" class="w-full md:w-1/2 text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200" required>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Items Blind Table -->
            <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-xl mt-4">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-900 text-slate-500 border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="p-3">#</th>
                            <th class="p-3">اسم الصنف</th>
                            <th class="p-3">وحدة القياس</th>
                            <th class="p-3 w-48">الكمية المجرودة فعلياً على الرف *</th>
                            <th class="p-3">ملاحظات أمين المخزن (إن وجدت)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($products as $idx => $prod)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30">
                                <td class="p-3 font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="p-3 font-bold text-slate-800 dark:text-slate-200">
                                    {{ $prod->name }}
                                    <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $prod->id }}">
                                </td>
                                <td class="p-3 text-slate-500 font-medium">{{ $prod->unit }}</td>
                                <td class="p-3">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0" 
                                        name="items[{{ $idx }}][physical_count]" 
                                        required 
                                        class="w-full p-2 border border-slate-300 dark:border-slate-600 rounded-lg text-xs font-mono font-bold bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                        placeholder="اكتب ما تم عده..."
                                    />
                                </td>
                                <td class="p-3">
                                    <input 
                                        type="text" 
                                        name="items[{{ $idx }}][discrepancy_reason]" 
                                        class="w-full p-2 border border-slate-300 dark:border-slate-600 rounded-lg text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                        placeholder="أي تلف أو كسر..."
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">ملاحظات عامة على الجلسة</label>
                <textarea name="notes" rows="2" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200" placeholder="أي تفاصيل عن حالة المخزن أو ظروف الجرد..."></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-blue-600/30 transition">
                إرسال محضر الجرد الأعمى للمحاسب للمطابقة
            </button>
        </div>
    </form>
</div>
@endsection
