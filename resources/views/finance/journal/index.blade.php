@extends('layouts.app')

@section('title', 'دفتر اليومية العامة والقيود التلقائية')

@section('control_panel')
    <x-control-panel title="دفتر اليومية العامة وسحب القيود التلقائية (Automated Journal Entries)" :breadcrumbs="['الحسابات والمالية' => route('finance.treasury.index'), 'دفتر اليومية' => route('finance.journal.index')]">
        <x-slot:actions>
            <a href="{{ route('finance.ledger.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="book-open" size="16" />
                <span>دفتر الأستاذ العام</span>
            </a>
            <a href="{{ route('finance.disbursements.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="truck" size="16" />
                <span>أذونات الصرف</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-success font-numeric px-2.5 py-1 text-xs">
                <span>القيود التلقائية:</span>
                <strong>متزنة آلياً (100%)</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-5" dir="rtl">
    <x-finance-nav />

    <!-- Filter & Search Bar -->
    <div class="erp-card p-4">
        <form action="{{ route('finance.journal.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div>
                <label class="erp-form-label text-xs">بحث برقم القيد أو البيان</label>
                <input type="text" name="search" class="erp-form-control text-xs" value="{{ request('search') }}" placeholder="رقم القيد أو الوصف...">
            </div>

            <div>
                <label class="erp-form-label text-xs">من تاريخ</label>
                <input type="date" name="start_date" class="erp-form-control text-xs font-mono" value="{{ request('start_date') }}">
            </div>

            <div>
                <label class="erp-form-label text-xs">إلى تاريخ</label>
                <input type="date" name="end_date" class="erp-form-control text-xs font-mono" value="{{ request('end_date') }}">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="erp-btn erp-btn-primary text-xs w-full">
                    <x-icon name="search" size="14" />
                    <span>تصفية القيود</span>
                </button>
                <a href="{{ route('finance.journal.index') }}" class="erp-btn erp-btn-secondary text-xs">
                    إلغاء
                </a>
            </div>
        </form>
    </div>

    <!-- Journal Entries List -->
    <div class="space-y-4">
        @forelse($entries as $entry)
            <div class="erp-card p-4 border border-slate-200 dark:border-slate-700">
                <!-- Entry Header -->
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-700 pb-3">
                    <div class="flex items-center gap-3">
                        <span class="font-mono font-bold text-primary text-sm">{{ $entry->entry_number }}</span>
                        <span class="erp-badge erp-badge-neutral font-mono text-xs">{{ $entry->entry_date->toDateString() }}</span>
                        <span class="erp-badge {{ $entry->source_type === 'disbursement' ? 'erp-badge-danger' : ($entry->source_type === 'receipt' ? 'erp-badge-success' : 'erp-badge-info') }} text-xs">
                            @if($entry->source_type === 'disbursement')
                                قيد صرف تلقائي
                            @elseif($entry->source_type === 'receipt')
                                قيد وارد تلقائي
                            @elseif($entry->source_type === 'closing')
                                قيد تسوية إقفال
                            @else
                                قيد يومية
                            @endif
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500">محرر القيد: {{ $entry->creator->name ?? 'النظام' }}</span>
                        <span class="erp-badge erp-badge-success text-[10px]">متزن</span>
                    </div>
                </div>

                <!-- Entry Description -->
                <div class="py-2.5 text-xs text-slate-700 dark:text-slate-200 font-semibold">
                    {{ $entry->description }}
                </div>

                <!-- Entry Lines Table -->
                <div class="overflow-x-auto rounded border border-slate-200 dark:border-slate-700">
                    <table class="erp-table text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60">
                                <th>اسم الحساب / الأستاذ المساعد</th>
                                <th>نوع الحساب</th>
                                <th class="text-left font-mono">مدين (منه)</th>
                                <th class="text-left font-mono">دائن (له)</th>
                                <th>البيان والملاحظات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entry->lines as $line)
                                <tr>
                                    <td class="font-bold text-slate-800 dark:text-slate-100">{{ $line->account_name }}</td>
                                    <td>
                                        <span class="erp-badge erp-badge-neutral text-[10px]">{{ $line->account_type }}</span>
                                    </td>
                                    <td class="font-mono text-left font-bold {{ $line->debit > 0 ? 'text-primary' : 'text-slate-400' }}">
                                        {{ $line->debit > 0 ? number_format($line->debit, 2) . ' ج.م' : '-' }}
                                    </td>
                                    <td class="font-mono text-left font-bold {{ $line->credit > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400' }}">
                                        {{ $line->credit > 0 ? number_format($line->credit, 2) . ' ج.م' : '-' }}
                                    </td>
                                    <td class="text-slate-500 text-[11px]">{{ $line->notes ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-100 dark:bg-slate-800 font-bold font-mono">
                                <td colspan="2" class="text-left">إجمالي القيد:</td>
                                <td class="text-left text-primary">{{ number_format($entry->total_debit, 2) }} ج.م</td>
                                <td class="text-left text-rose-600 dark:text-rose-400">{{ number_format($entry->total_credit, 2) }} ج.م</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @empty
            <div class="erp-card p-12 text-center text-slate-400">
                <x-icon name="file-text" size="36" class="mx-auto mb-2 opacity-50" />
                <p>لا توجد قيود يومية مسجلة وفق شروط البحث الحالية.</p>
            </div>
        @endforelse

        @if($entries->hasPages())
            <div class="p-4 erp-card">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
