@extends('layouts.app')

@section('content')
<div class="p-6 max-w-5xl mx-auto space-y-6" dir="rtl">
    <!-- Header -->
    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>🚚</span>
                <span>إصدار إذن تحميل وصرف لسيارة المندوب</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                صرف الخامات من المخزن ونقلها كعهدة لسيارة السائق بنظام FIFO شاملة نسبة الأمان للزيادات الطارئة.
            </p>
        </div>
        <a href="{{ route('warehouse.dispatches') }}" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl transition">
            رجوع للقائمة
        </a>
    </div>

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('warehouse.dispatches.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Main Info Box -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 border-b border-slate-100 dark:border-slate-700 pb-2">بيانات التوريد والسيارة</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Hospital -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">المستشفى / الجهة المستهدفة *</label>
                    <select name="hospital_id" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200" required>
                        <option value="">اختر المستشفى...</option>
                        @foreach($hospitals as $hosp)
                            <option value="{{ $hosp->id }}" {{ old('hospital_id') == $hosp->id ? 'selected' : '' }}>{{ $hosp->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Source Warehouse -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">المخزن المصدر للصرف *</label>
                    <select name="source_warehouse_id" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200" required>
                        @foreach($sourceWarehouses as $swh)
                            <option value="{{ $swh->id }}" {{ old('source_warehouse_id') == $swh->id ? 'selected' : '' }}>{{ $swh->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Transit Van Warehouse -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">سيارة المندوب (المخزن المتنقل) *</label>
                    <select name="transit_warehouse_id" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200" required>
                        @foreach($transitWarehouses as $twh)
                            <option value="{{ $twh->id }}" {{ old('transit_warehouse_id') == $twh->id ? 'selected' : '' }}>{{ $twh->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Driver -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">السائق المستلم للعهدة *</label>
                    <select name="driver_id" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200" required>
                        @foreach($drivers as $drv)
                            <option value="{{ $drv->id }}" {{ old('driver_id') == $drv->id ? 'selected' : '' }}>{{ $drv->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Items Loading Box -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 border-b border-slate-100 dark:border-slate-700 pb-2">أصناف التحميل والكميات المقننة ونسبة الأمان</h2>

            <div class="space-y-3" id="dispatchItemsContainer">
                @for($i = 0; $i < 3; $i++)
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-700">
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">الصنف</label>
                            <select name="items[{{ $i }}][product_id]" class="w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                                <option value="">اختر الصنف...</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}" {{ $i == 0 && $loop->first ? 'selected' : '' }}>{{ $prod->name }} ({{ $prod->unit }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">الكمية المطلوبة (عقد)</label>
                            <input type="number" step="0.01" name="items[{{ $i }}][planned_qty]" value="{{ $i == 0 ? '50' : '' }}" class="w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-mono" placeholder="0.00" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">كمية الأمان (Buffer للزيادات)</label>
                            <input type="number" step="0.01" name="items[{{ $i }}][buffer_qty]" value="{{ $i == 0 ? '5' : '' }}" class="w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-mono" placeholder="0.00" />
                        </div>
                    </div>
                @endfor
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-blue-600/30 transition">
                تأكيد الصرف والتحميل لسيارة المندوب بنظام FIFO
            </button>
        </div>
    </form>
</div>
@endsection
