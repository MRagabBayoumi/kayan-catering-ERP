@extends('layouts.app')

@section('content')
<div class="p-6 max-w-5xl mx-auto space-y-6" dir="rtl">
    <!-- Header -->
    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>⚖️</span>
                <span>اعتماد تسوية رحلة توريد المندوب: {{ $dispatch->driver->name ?? '-' }}</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                إذن تحميل رقم [{{ $dispatch->dispatch_number }}] | المستشفى: {{ $dispatch->hospital->name ?? '-' }}
            </p>
        </div>
        <a href="{{ route('warehouse.dispatches') }}" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl transition">
            رجوع للأذون
        </a>
    </div>

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('warehouse.settlements.store') }}" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" name="dispatch_order_id" value="{{ $dispatch->id }}">

        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 border-b border-slate-100 dark:border-slate-700 pb-2">بيانات إشعار استلام المستشفى</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">اسم مسؤول الاستلام بالمستشفى الموقع على المحضر *</label>
                    <input type="text" name="receiving_officer_name" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200" placeholder="مثلاً: د. أحمد فؤاد (مشرف التغذية)" value="{{ old('receiving_officer_name') }}" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">ملاحظات المحاسب على التسوية</label>
                    <input type="text" name="notes" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200" placeholder="أي فوارق أو بنود تم استعاضتها..." value="{{ old('notes') }}" />
                </div>
            </div>

            <!-- Items Clearance Table -->
            <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-xl mt-4">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="p-3">الصنف</th>
                            <th class="p-3 text-center">المحمل بالسيارة</th>
                            <th class="p-3 text-center w-36">المسلم للمستشفى فعلياً *</th>
                            <th class="p-3 text-center w-36">المرتجع للمخزن *</th>
                            <th class="p-3 text-center w-32">زيادة طلبتها المستشفى</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($dispatch->items as $idx => $item)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30">
                                <td class="p-3 font-bold text-slate-800 dark:text-slate-200">
                                    {{ $item->product->name ?? '-' }}
                                    <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $item->product_id }}">
                                    <input type="hidden" name="items[{{ $idx }}][dispatched_qty]" value="{{ $item->total_dispatched_qty }}">
                                </td>
                                <td class="p-3 font-mono text-center font-extrabold text-blue-600 dark:text-blue-400">
                                    {{ number_format($item->total_dispatched_qty, 2) }} {{ $item->product->unit ?? 'كجم' }}
                                </td>
                                <td class="p-3">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0" 
                                        name="items[{{ $idx }}][actual_delivered_qty]" 
                                        value="{{ old("items.$idx.actual_delivered_qty", $item->planned_qty) }}" 
                                        required 
                                        class="w-full p-2 border border-slate-300 dark:border-slate-600 rounded-lg text-xs font-mono font-bold bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    />
                                </td>
                                <td class="p-3">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0" 
                                        name="items[{{ $idx }}][returned_qty]" 
                                        value="{{ old("items.$idx.returned_qty", $item->buffer_qty) }}" 
                                        required 
                                        class="w-full p-2 border border-slate-300 dark:border-slate-600 rounded-lg text-xs font-mono font-bold bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    />
                                </td>
                                <td class="p-3">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0" 
                                        name="items[{{ $idx }}][extra_requested_qty]" 
                                        value="{{ old("items.$idx.extra_requested_qty", '0') }}" 
                                        class="w-full p-2 border border-slate-300 dark:border-slate-600 rounded-lg text-xs font-mono bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-600/30 transition">
                اعتماد التسوية وإرجاع البضاعة وتوليد قيد الـ ERP
            </button>
        </div>
    </form>
</div>
@endsection
