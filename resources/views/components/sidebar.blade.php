<aside id="erpSidebar" class="erp-sidebar" dir="rtl">
    <!-- Company Box -->
    <div class="erp-sidebar-header">
        <div class="erp-company-card">
            @if(!empty($globalCompanySetting?->logo_url))
                <img src="{{ $globalCompanySetting->logo_url }}" alt="{{ $globalCompanySetting?->company_name }}" class="erp-company-logo erp-company-logo-img">
            @else
                <div class="erp-company-logo">KC</div>
            @endif
            <div class="erp-company-info">
                <div class="erp-company-name" title="{{ $globalCompanySetting?->company_name_ar ?? 'كيان للكيترينج والتغذية' }}">
                    {{ $globalCompanySetting?->company_name_ar ?? 'كيان للكيترينج والتغذية' }}
                </div>
                <div class="erp-company-sub">
                    <span>توريد الأغذية وإعاشة المستشفيات</span>
                </div>
            </div>
        </div>
        <button id="sidebarDesktopToggle" class="erp-sidebar-toggle-btn" type="button" title="طي / توسيع القائمة" aria-label="طي أو توسيع القائمة الجانبية">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </button>
    </div>

    <!-- Navigation List (Collapsible Sections) -->
    <nav class="erp-sidebar-nav">
        <!-- Overview -->
        <div class="erp-nav-section mb-2">
            <div class="erp-nav-section-title">نظرة عامة</div>
            <div class="erp-nav-section-items">
                <a href="{{ route('dashboard') }}" class="erp-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="لوحة التحكم والمؤشرات">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="layout-dashboard" size="18" /></span>
                        <span class="erp-nav-label">لوحة التحكم العامة</span>
                    </div>
                </a>
                <button type="button" data-action="open-ai-copilot" class="erp-nav-item erp-nav-item-ai w-full text-right">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon text-[var(--primary)]"><x-icon name="sparkles" size="18" /></span>
                        <span class="erp-nav-label font-bold text-[var(--primary)]">مساعد كيان الذكي (AI)</span>
                    </div>
                </button>
            </div>
        </div>

        <!-- 1. Warehouse Department (إدارة المخازن والـ FIFO) -->
        <div class="erp-nav-section is-open" data-section="warehouse">
            <button type="button" class="erp-nav-section-header" aria-expanded="true">
                <span class="erp-nav-section-title">إدارة المخازن والتشغيلات (FIFO)</span>
                <svg class="erp-section-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div class="erp-nav-section-items">
                <a href="{{ route('warehouse.index') }}" class="erp-nav-item {{ request()->routeIs('warehouse.index') ? 'active' : '' }}" title="أرصدة وتشغيلات الباتشات">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="layers" size="18" /></span>
                        <span class="erp-nav-label">تشغيلات الشراء (Batches FIFO)</span>
                    </div>
                </a>
                <a href="{{ route('warehouse.dispatches') }}" class="erp-nav-item {{ request()->routeIs('warehouse.dispatches*') ? 'active' : '' }}" title="أذون التحميل والصرف لسيارات المناديب">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="truck" size="18" /></span>
                        <span class="erp-nav-label">أذون التحميل والصرف (السيارات)</span>
                    </div>
                </a>
                <a href="{{ route('warehouse.audits.index') }}" class="erp-nav-item {{ request()->routeIs('warehouse.audits*') ? 'active' : '' }}" title="الجرد اليومي الأعمى لأمين المخزن">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="clipboard-check" size="18" /></span>
                        <span class="erp-nav-label">الجرد اليومي الأعمى (Blind Audit)</span>
                    </div>
                </a>
                <a href="{{ route('warehouse.settlements.index') }}" class="erp-nav-item {{ request()->routeIs('warehouse.settlements*') ? 'active' : '' }}" title="تسوية عهدة السائق ومخرجات الـ ERP">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="receipt" size="18" /></span>
                        <span class="erp-nav-label">تسوية عهد المناديب (محاسب المخزن)</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- 2. Costing Department (إدارة التكاليف والمقننات) -->
        <div class="erp-nav-section is-open" data-section="costing">
            <button type="button" class="erp-nav-section-header" aria-expanded="true">
                <span class="erp-nav-section-title">إدارة التكاليف والمقننات (BOM)</span>
                <svg class="erp-section-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div class="erp-nav-section-items">
                <a href="{{ Route::has('costing.recipes.index') ? route('costing.recipes.index') : url('/costing/recipes') }}" class="erp-nav-item {{ request()->routeIs('costing.recipes*') ? 'active' : '' }}" title="المقننات الغذائية القياسية">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="book-open" size="18" /></span>
                        <span class="erp-nav-label">المقننات القياسية للوجبات (BOM)</span>
                    </div>
                </a>
                <a href="{{ Route::has('costing.daily-order') ? route('costing.daily-order') : url('/costing/daily-order') }}" class="erp-nav-item {{ request()->routeIs('costing.daily-order*') ? 'active' : '' }}" title="حساب طلبيات المطبخ بناء على عدد النزلاء">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="calculator" size="18" /></span>
                        <span class="erp-nav-label">حساب طلبيات المطبخ (المقنن)</span>
                    </div>
                </a>
                <a href="{{ Route::has('costing.consumption.index') ? route('costing.consumption.index') : url('/costing/consumption') }}" class="erp-nav-item {{ request()->routeIs('costing.consumption*') ? 'active' : '' }}" title="كشف انحرافات المقنن والهدر المالي">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="alert-triangle" size="18" /></span>
                        <span class="erp-nav-label">كشف انحرافات المقنن (Variance)</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- 3. Sales & Tenders Department (إدارة المبيعات والمناقصات) -->
        <div class="erp-nav-section is-open" data-section="sales">
            <button type="button" class="erp-nav-section-header" aria-expanded="true">
                <span class="erp-nav-section-title">إدارة المبيعات والمناقصات والعقود</span>
                <svg class="erp-section-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div class="erp-nav-section-items">
                <a href="{{ Route::has('sales.hospitals.index') ? route('sales.hospitals.index') : url('/sales/hospitals') }}" class="erp-nav-item {{ request()->routeIs('sales.hospitals*') ? 'active' : '' }}" title="المستشفيات والجهات المتعاقدة">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="building-2" size="18" /></span>
                        <span class="erp-nav-label">المستشفيات والتعاقدات</span>
                    </div>
                </a>
                <a href="{{ Route::has('sales.tenders.index') ? route('sales.tenders.index') : url('/sales/tenders') }}" class="erp-nav-item {{ request()->routeIs('sales.tenders*') ? 'active' : '' }}" title="سجل المناقصات وكراسات الشروط">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="file-text" size="18" /></span>
                        <span class="erp-nav-label">المناقصات والعطاءات (Tenders)</span>
                    </div>
                </a>
                <a href="{{ Route::has('sales.competitors.index') ? route('sales.competitors.index') : url('/sales/competitors') }}" class="erp-nav-item {{ request()->routeIs('sales.competitors*') ? 'active' : '' }}" title="أرشيف أسعار المنافسين للعام القادم">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="archive" size="18" /></span>
                        <span class="erp-nav-label">أرشيف أسعار المنافسين</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- 4. Procurement Department (إدارة المشتريات والتوريدات) -->
        <div class="erp-nav-section {{ request()->is('procurement*') ? 'is-open' : '' }}" data-section="procurement">
            <button type="button" class="erp-nav-section-header" aria-expanded="{{ request()->is('procurement*') ? 'true' : 'false' }}">
                <span class="erp-nav-section-title">إدارة المشتريات والتوريدات</span>
                <svg class="erp-section-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div class="erp-nav-section-items">
                <a href="{{ route('procurement.orders.index') }}" class="erp-nav-item {{ request()->routeIs('procurement.orders*') ? 'active' : '' }}" title="طلبات الشراء وعروض الأسعار">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="shopping-bag" size="18" /></span>
                        <span class="erp-nav-label">طلبات الشراء وعروض الأسعار</span>
                    </div>
                </a>
                <a href="{{ route('procurement.suppliers.index') }}" class="erp-nav-item {{ request()->routeIs('procurement.suppliers*') ? 'active' : '' }}" title="دليل وتكويد الموردين المعتمدين">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="factory" size="18" /></span>
                        <span class="erp-nav-label">دليل وتكويد الموردين</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- 5. Finance & Accounts Department (إدارة الحسابات والمالية) -->
        <div class="erp-nav-section {{ request()->is('finance*') ? 'is-open' : '' }}" data-section="finance">
            <button type="button" class="erp-nav-section-header" aria-expanded="{{ request()->is('finance*') ? 'true' : 'false' }}">
                <span class="erp-nav-section-title">إدارة الحسابات والمالية</span>
                <svg class="erp-section-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div class="erp-nav-section-items">
                <a href="{{ route('finance.suppliers.index') }}" class="erp-nav-item {{ request()->routeIs('finance.suppliers*') ? 'active' : '' }}" title="حسابات الموردين وجدولة السداد">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="credit-card" size="18" /></span>
                        <span class="erp-nav-label">حسابات الموردين والآجل</span>
                    </div>
                </a>
                <a href="{{ route('finance.treasury.index') }}" class="erp-nav-item {{ request()->routeIs('finance.treasury*') ? 'active' : '' }}" title="الخزينة والبنوك وعهد المواقع">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="wallet" size="18" /></span>
                        <span class="erp-nav-label">الخزينة وعهد المواقع النقدية</span>
                    </div>
                </a>
                <a href="{{ route('finance.closing.index') }}" class="erp-nav-item {{ request()->routeIs('finance.closing*') ? 'active' : '' }}" title="التقفيل اليومي لصاحب العمل">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="pie-chart" size="18" /></span>
                        <span class="erp-nav-label">اليومية والتقفيل لصاحب العمل</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- 6. System Settings & Permissions (إعدادات النظام والصلاحيات) -->
        <div class="erp-nav-section {{ (request()->is('settings*') || request()->is('users*') || request()->is('roles*')) ? 'is-open' : '' }}" data-section="settings">
            <button type="button" class="erp-nav-section-header" aria-expanded="{{ (request()->is('settings*') || request()->is('users*') || request()->is('roles*')) ? 'true' : 'false' }}">
                <span class="erp-nav-section-title">إعدادات النظام والصلاحيات</span>
                <svg class="erp-section-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div class="erp-nav-section-items">
                <a href="{{ route('users.index') }}" class="erp-nav-item {{ request()->routeIs('users*') ? 'active' : '' }}" title="إدارة المستخدمين وحسابات الدخول">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="users" size="18" /></span>
                        <span class="erp-nav-label">المستخدمين وحسابات الدخول</span>
                    </div>
                </a>
                <a href="{{ route('roles.index') }}" class="erp-nav-item {{ request()->routeIs('roles*') ? 'active' : '' }}" title="الأدوار ومصفوفة الصلاحيات">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="shield" size="18" /></span>
                        <span class="erp-nav-label">الأدوار ومصفوفة الصلاحيات</span>
                    </div>
                </a>
                <a href="{{ route('settings.index') }}" class="erp-nav-item {{ request()->routeIs('settings*') ? 'active' : '' }}" title="إعدادات الشركة والنظام">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="settings" size="18" /></span>
                        <span class="erp-nav-label">إعدادات الشركة والنظام</span>
                    </div>
                </a>
            </div>
        </div>
    </nav>
</aside>
