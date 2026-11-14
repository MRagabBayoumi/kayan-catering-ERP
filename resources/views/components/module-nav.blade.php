@props([
    'tabs' => [],
])

@if(!empty($tabs))
<div class="erp-card erp-tab-nav-card mb-4" dir="rtl">
    <nav class="erp-tab-bar" aria-label="تبويبات الموديول الفرعية">
        @foreach($tabs as $tab)
            <a href="{{ $tab['url'] }}" 
               class="erp-tab-link {{ ($tab['active'] ?? false) ? 'active' : '' }}" 
               title="{{ $tab['title'] ?? $tab['label'] }}">
                @if(!empty($tab['icon']))
                    <x-icon :name="$tab['icon']" size="16" />
                @endif
                <span>{{ $tab['label'] }}</span>
                @if(isset($tab['badge']))
                    <span class="erp-badge {{ $tab['badge_class'] ?? 'erp-badge-neutral' }} text-[10px] px-1.5 py-0.2 font-numeric">
                        {{ $tab['badge'] }}
                    </span>
                @endif
            </a>
        @endforeach
    </nav>
</div>
@endif
