@php
    $currentModule = null;
    $moduleTitle = 'لوحة التحكم العامة';
    $moduleIcon = 'layout-dashboard';
    $moduleColorClass = 'odoo-icon-violet';

    if (request()->is('warehouse*')) {
        $currentModule = 'warehouse';
        $moduleTitle = 'إدارة المخازن والتشغيلات (FIFO)';
        $moduleIcon = 'warehouse';
        $moduleColorClass = 'odoo-icon-blue';
    } elseif (request()->is('costing*')) {
        $currentModule = 'costing';
        $moduleTitle = 'إدارة التكاليف والمقننات (BOM)';
        $moduleIcon = 'calculator';
        $moduleColorClass = 'odoo-icon-emerald';
    } elseif (request()->is('site*')) {
        $currentModule = 'site';
        $moduleTitle = 'تشغيل المواقع والمطابخ';
        $moduleIcon = 'calendar';
        $moduleColorClass = 'odoo-icon-amber';
    } elseif (request()->is('sales*')) {
        $currentModule = 'sales';
        $moduleTitle = 'المبيعات والمناقصات';
        $moduleIcon = 'building-2';
        $moduleColorClass = 'odoo-icon-cyan';
    } elseif (request()->is('procurement*')) {
        $currentModule = 'procurement';
        $moduleTitle = 'إدارة المشتريات والتوريدات';
        $moduleIcon = 'shopping-bag';
        $moduleColorClass = 'odoo-icon-teal';
    } elseif (request()->is('finance*')) {
        $currentModule = 'finance';
        $moduleTitle = 'إدارة الحسابات والمالية';
        $moduleIcon = 'wallet';
        $moduleColorClass = 'odoo-icon-purple';
    } elseif (request()->is('settings*') || request()->is('users*') || request()->is('roles*')) {
        $currentModule = 'settings';
        $moduleTitle = 'إعدادات النظام والصلاحيات';
        $moduleIcon = 'settings';
        $moduleColorClass = 'odoo-icon-gray';
    } else {
        $currentModule = 'dashboard';
        $moduleTitle = 'لوحة المؤشرات والرقابة';
        $moduleIcon = 'layout-dashboard';
        $moduleColorClass = 'odoo-icon-violet';
    }
@endphp

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

    <!-- Active Module Banner & Return to Home Button (Odoo Architecture) -->
    <div class="erp-sidebar-module-banner">
        <a href="{{ route('home') }}" class="erp-sidebar-home-btn" title="العودة لشاشة التطبيقات الرئيسية (Home)">
            <x-icon name="arrow-right" size="14" />
            <span>العودة لكل التطبيقات</span>
        </a>
        <div class="erp-sidebar-module-info">
            <span class="erp-sidebar-module-icon {{ $moduleColorClass }}">
                <x-icon :name="$moduleIcon" size="18" />
            </span>
            <strong class="erp-sidebar-module-title">{{ $moduleTitle }}</strong>
        </div>
    </div>

    <!-- Navigation List (Isolated Sub-Sections for Current Module Only) -->
    <nav class="erp-sidebar-nav">

        {{-- 1. WAREHOUSE MODULE SUB-SECTIONS --}}
        @if($currentModule === 'warehouse')
            @can('manage-warehouse')
            <!-- القسم الفرعي 1: حركة المخزون والتشغيلات -->
            <div class="erp-nav-section is-open" data-section="warehouse-stock">
                <div class="erp-nav-section-title">حركة المخزون والتشغيلات</div>
                <div class="erp-nav-section-items">
                    <a href="{{ route('warehouse.index') }}" class="erp-nav-item {{ request()->routeIs('warehouse.index') ? 'active' : '' }}" title="أرصدة وتشغيلات الباتشات">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="layers" size="18" /></span>
                            <span class="erp-nav-label">تشغيلات الشراء (Batches FIFO)</span>
                        </div>
                    </a>
                    <a href="{{ route('warehouse.expiry') }}" class="erp-nav-item {{ request()->routeIs('warehouse.expiry*') ? 'active' : '' }}" title="محرك الصلاحيات وتنبيهات FEFO الذكية">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="alert-triangle" size="18" /></span>
                            <span class="erp-nav-label">الصلاحيات وتنبيهات FEFO</span>
                        </div>
                    </a>
                    <a href="{{ route('warehouse.audits.index') }}" class="erp-nav-item {{ request()->routeIs('warehouse.audits*') ? 'active' : '' }}" title="الجرد اليومي الأعمى لأمين المخزن">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="clipboard-check" size="18" /></span>
                            <span class="erp-nav-label">الجرد اليومي الأعمى (Blind Audit)</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- القسم الفرعي 2: التوزيع والتسويات -->
            <div class="erp-nav-section is-open" data-section="warehouse-dispatch">
                <div class="erp-nav-section-title">التوزيع والتسويات</div>
                <div class="erp-nav-section-items">
                    <a href="{{ route('warehouse.dispatches') }}" class="erp-nav-item {{ request()->routeIs('warehouse.dispatches*') ? 'active' : '' }}" title="أذون التحميل والصرف لسيارات المناديب">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="truck" size="18" /></span>
                            <span class="erp-nav-label">أذون التحميل والصرف (السيارات)</span>
                        </div>
                    </a>
                    <a href="{{ route('warehouse.trips.index') }}" class="erp-nav-item {{ request()->routeIs('warehouse.trips*') ? 'active' : '' }}" title="حركة سيارات التوزيع وتوزيع الوقود المشترك">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="truck" size="18" /></span>
                            <span class="erp-nav-label">حركة السيارات ومصاريف النقل</span>
                        </div>
                    </a>
                    @can('manage-settlements')
                    <a href="{{ route('warehouse.settlements.index') }}" class="erp-nav-item {{ request()->routeIs('warehouse.settlements*') ? 'active' : '' }}" title="تسوية عهدة السائق ومخرجات الـ ERP">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="receipt" size="18" /></span>
                            <span class="erp-nav-label">تسوية عهد المناديب (محاسب المخزن)</span>
                        </div>
                    </a>
                    @endcan
                </div>
            </div>
            @endcan
        @endif

        {{-- 2. COSTING MODULE SUB-SECTIONS --}}
        @if($currentModule === 'costing')
            @can('manage-costing')
            <!-- القسم الفرعي 1: المقننات والطلبيات -->
            <div class="erp-nav-section is-open" data-section="costing-recipes">
                <div class="erp-nav-section-title">المقننات والطلبيات القياسية</div>
                <div class="erp-nav-section-items">
                    <a href="{{ route('costing.recipes.index') }}" class="erp-nav-item {{ request()->routeIs('costing.recipes*') ? 'active' : '' }}" title="المقننات الغذائية القياسية">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="book-open" size="18" /></span>
                            <span class="erp-nav-label">المقننات القياسية للوجبات (BOM)</span>
                        </div>
                    </a>
                    <a href="{{ route('costing.daily-order') }}" class="erp-nav-item {{ request()->routeIs('costing.daily-order*') ? 'active' : '' }}" title="حساب طلبيات المطبخ بناء على عدد النزلاء">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="calculator" size="18" /></span>
                            <span class="erp-nav-label">حساب طلبيات المطبخ (المقنن)</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- القسم الفرعي 2: الرقابة والانحرافات -->
            <div class="erp-nav-section is-open" data-section="costing-control">
                <div class="erp-nav-section-title">الرقابة وتحليل الانحرافات والهدر</div>
                <div class="erp-nav-section-items">
                    <a href="{{ route('costing.consumption.index') }}" class="erp-nav-item {{ request()->routeIs('costing.consumption*') ? 'active' : '' }}" title="كشف انحرافات المقنن والهدر المالي">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="alert-triangle" size="18" /></span>
                            <span class="erp-nav-label">كشف انحرافات المقنن (Variance)</span>
                        </div>
                    </a>
                    <a href="{{ route('costing.reconciliation.index') }}" class="erp-nav-item {{ request()->routeIs('costing.reconciliation*') ? 'active' : '' }}" title="مطابقة أعداد المرضى ومنصرف المطبخ الفعلي">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="clipboard-check" size="18" /></span>
                            <span class="erp-nav-label">مطابقة الموقع وكشف الهدر/العجز</span>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
        @endif

        {{-- 3. SITE OPERATIONS SUB-SECTIONS --}}
        @if($currentModule === 'site')
            @can('manage-site-operations')
            <div class="erp-nav-section is-open" data-section="site-ops">
                <div class="erp-nav-section-title">تشغيل المواقع والمطابخ</div>
                <div class="erp-nav-section-items">
                    <a href="{{ route('site.census.index') }}" class="erp-nav-item {{ request()->routeIs('site.census*') ? 'active' : '' }}" title="تسجيل تشغيل وأعداد الموقع اليومية">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="calendar" size="18" /></span>
                            <span class="erp-nav-label">أعداد وتشغيل اليوم (لحم/فراخ)</span>
                        </div>
                    </a>
                    <a href="{{ route('site.kitchen-issues.index') }}" class="erp-nav-item {{ request()->routeIs('site.kitchen-issues*') ? 'active' : '' }}" title="صرف خامات المخزن الفرعي لشيف المطبخ">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="package" size="18" /></span>
                            <span class="erp-nav-label">صرف المخزن الفرعي للمطبخ</span>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
        @endif

        {{-- 4. SALES & TENDERS SUB-SECTIONS --}}
        @if($currentModule === 'sales')
            @can('manage-sales')
            <!-- القسم الفرعي 1: عقود المستشفيات -->
            <div class="erp-nav-section is-open" data-section="sales-hospitals">
                <div class="erp-nav-section-title">عقود المستشفيات والجهات</div>
                <div class="erp-nav-section-items">
                    <a href="{{ route('sales.hospitals.index') }}" class="erp-nav-item {{ request()->routeIs('sales.hospitals*') ? 'active' : '' }}" title="المستشفيات والجهات المتعاقدة">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="building-2" size="18" /></span>
                            <span class="erp-nav-label">المستشفيات والتعاقدات</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- القسم الفرعي 2: المناقصات والمنافسين -->
            <div class="erp-nav-section is-open" data-section="sales-tenders">
                <div class="erp-nav-section-title">المناقصات وتحليل المنافسين</div>
                <div class="erp-nav-section-items">
                    <a href="{{ route('sales.tenders.index') }}" class="erp-nav-item {{ request()->routeIs('sales.tenders*') ? 'active' : '' }}" title="سجل المناقصات وكراسات الشروط">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="file-text" size="18" /></span>
                            <span class="erp-nav-label">المناقصات والعطاءات (Tenders)</span>
                        </div>
                    </a>
                    <a href="{{ route('sales.competitors.index') }}" class="erp-nav-item {{ request()->routeIs('sales.competitors*') ? 'active' : '' }}" title="أرشيف أسعار المنافسين للعام القادم">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="archive" size="18" /></span>
                            <span class="erp-nav-label">أرشيف أسعار المنافسين</span>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
        @endif

        {{-- 5. PROCUREMENT SUB-SECTIONS --}}
        @if($currentModule === 'procurement')
            @can('manage-procurement')
            <!-- القسم الفرعي 1: أوامر التوريد والشراء -->
            <div class="erp-nav-section is-open" data-section="procurement-orders">
                <div class="erp-nav-section-title">أوامر التوريد والشراء</div>
                <div class="erp-nav-section-items">
                    <a href="{{ route('procurement.orders.index') }}" class="erp-nav-item {{ request()->routeIs('procurement.orders*') ? 'active' : '' }}" title="طلبات الشراء وعروض الأسعار">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="shopping-bag" size="18" /></span>
                            <span class="erp-nav-label">طلبات الشراء وعروض الأسعار</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- القسم الفرعي 2: دليل الموردين المعتمدين -->
            <div class="erp-nav-section is-open" data-section="procurement-suppliers">
                <div class="erp-nav-section-title">شبكة الموردين المعتمدين</div>
                <div class="erp-nav-section-items">
                    <a href="{{ route('procurement.suppliers.index') }}" class="erp-nav-item {{ request()->routeIs('procurement.suppliers*') ? 'active' : '' }}" title="دليل وتكويد الموردين المعتمدين">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="factory" size="18" /></span>
                            <span class="erp-nav-label">دليل وتكويد الموردين</span>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
        @endif

        {{-- 6. FINANCE & ACCOUNTS SUB-SECTIONS --}}
        @if($currentModule === 'finance')
            @can('manage-finance')
            <!-- القسم الفرعي 1: الخزينة والعهد النقدية -->
            <div class="erp-nav-section is-open" data-section="finance-cash">
                <div class="erp-nav-section-title">الخزينة والعهد النقدية</div>
                <div class="erp-nav-section-items">
                    <a href="{{ route('finance.treasury.index') }}" class="erp-nav-item {{ request()->routeIs('finance.treasury*') ? 'active' : '' }}" title="الخزينة والبنوك وعهد المواقع">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="wallet" size="18" /></span>
                            <span class="erp-nav-label">الخزينة وعهد المواقع النقدية</span>
                        </div>
                    </a>
                    <a href="{{ route('finance.disbursements.index') }}" class="erp-nav-item {{ request()->routeIs('finance.disbursements*') ? 'active' : '' }}" title="أذونات الصرف اللحظية من الإدارة لأمين الخزنة">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="truck" size="18" /></span>
                            <span class="erp-nav-label">أذونات الصرف اللحظية</span>
                        </div>
                    </a>
                    <a href="{{ route('finance.receipts.index') }}" class="erp-nav-item {{ request()->routeIs('finance.receipts*') ? 'active' : '' }}" title="الوارد واعتماد التحصيلات والترصيد">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="arrow-down" size="18" /></span>
                            <span class="erp-nav-label">الوارد واعتماد التحصيلات</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- القسم الفرعي 2: حسابات الموردين والمديونيات -->
            <div class="erp-nav-section is-open" data-section="finance-suppliers">
                <div class="erp-nav-section-title">الموردين والمديونيات</div>
                <div class="erp-nav-section-items">
                    <a href="{{ route('finance.suppliers.index') }}" class="erp-nav-item {{ request()->routeIs('finance.suppliers*') ? 'active' : '' }}" title="حسابات الموردين وجدولة السداد">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="credit-card" size="18" /></span>
                            <span class="erp-nav-label">حسابات الموردين والآجل</span>
                        </div>
                    </a>
                    <a href="{{ route('finance.settlements.index') }}" class="erp-nav-item {{ request()->routeIs('finance.settlements*') ? 'active' : '' }}" title="ترصيد وتسوية الموردين والعهد">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="clipboard-check" size="18" /></span>
                            <span class="erp-nav-label">ترصيد الموردين والعهد</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- القسم الفرعي 3: الحسابات العامة واليومية -->
            <div class="erp-nav-section is-open" data-section="finance-ledger">
                <div class="erp-nav-section-title">الحسابات العامة والتقفيل</div>
                <div class="erp-nav-section-items">
                    <a href="{{ route('finance.journal.index') }}" class="erp-nav-item {{ request()->routeIs('finance.journal*') ? 'active' : '' }}" title="دفتر اليومية العامة والقيود التلقائية">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="file-text" size="18" /></span>
                            <span class="erp-nav-label">دفتر اليومية والقيود التلقائية</span>
                        </div>
                    </a>
                    <a href="{{ route('finance.ledger.index') }}" class="erp-nav-item {{ request()->routeIs('finance.ledger*') ? 'active' : '' }}" title="دفتر الأستاذ العام وكشوف الحسابات">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="book-open" size="18" /></span>
                            <span class="erp-nav-label">دفتر الأستاذ العام</span>
                        </div>
                    </a>
                    <a href="{{ route('finance.breakdown.index') }}" class="erp-nav-item {{ request()->routeIs('finance.breakdown*') ? 'active' : '' }}" title="التفريدة والتحليل الشهري للجهات">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="calendar" size="18" /></span>
                            <span class="erp-nav-label">التفريدة الشهرية للجهات</span>
                        </div>
                    </a>
                    @can('manage-closing')
                    <a href="{{ route('finance.closing.index') }}" class="erp-nav-item {{ request()->routeIs('finance.closing*') ? 'active' : '' }}" title="محضر تقفيل اليومية ومطابقة العجز">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="pie-chart" size="18" /></span>
                            <span class="erp-nav-label">اليومية والتقفيل ومحضر العجز</span>
                        </div>
                    </a>
                    @endcan
                </div>
            </div>
            @endcan
        @endif

        {{-- 7. SETTINGS & USERS SUB-SECTIONS --}}
        @if($currentModule === 'settings')
            @can('manage-company-settings')
            <!-- القسم الفرعي 1: إدارة المستخدمين والدخول -->
            <div class="erp-nav-section is-open" data-section="settings-users">
                <div class="erp-nav-section-title">إدارة المستخدمين والصلاحيات</div>
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
                </div>
            </div>

            <!-- القسم الفرعي 2: تهيئة النظام والشركة -->
            <div class="erp-nav-section is-open" data-section="settings-company">
                <div class="erp-nav-section-title">تهيئة النظام والشركة</div>
                <div class="erp-nav-section-items">
                    <a href="{{ route('settings.index') }}" class="erp-nav-item {{ request()->routeIs('settings*') ? 'active' : '' }}" title="إعدادات الشركة والنظام">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="settings" size="18" /></span>
                            <span class="erp-nav-label">إعدادات الشركة والنظام</span>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
        @endif

        {{-- 8. DASHBOARD OVERVIEW SUB-SECTIONS --}}
        @if($currentModule === 'dashboard')
            @can('manage-dashboard')
            <div class="erp-nav-section is-open" data-section="dashboard-overview">
                <div class="erp-nav-section-title">لوحة الرقابة والمؤشرات</div>
                <div class="erp-nav-section-items">
                    <a href="{{ route('dashboard') }}" class="erp-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="لوحة التحكم والمؤشرات">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="layout-dashboard" size="18" /></span>
                            <span class="erp-nav-label">مؤشرات الأداء العامة (KPIs)</span>
                        </div>
                    </a>
                    <a href="{{ route('warehouse.expiry') }}" class="erp-nav-item {{ request()->routeIs('warehouse.expiry*') ? 'active' : '' }}" title="تنبيهات الصلاحية الحرجة">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="alert-triangle" size="18" /></span>
                            <span class="erp-nav-label">تنبيهات الصلاحية الحرجة</span>
                        </div>
                    </a>
                    <a href="{{ route('warehouse.trips.index') }}" class="erp-nav-item {{ request()->routeIs('warehouse.trips*') ? 'active' : '' }}" title="حركة سيارات التوزيع">
                        <div class="erp-nav-content">
                            <span class="erp-nav-icon"><x-icon name="truck" size="18" /></span>
                            <span class="erp-nav-label">حركة سيارات التوزيع</span>
                        </div>
                    </a>
                </div>
            </div>
            @endcan
        @endif

    </nav>

    <!-- Sidebar Footer: Persistent AI Copilot Trigger -->
    @can('use-ai-copilot')
    <div class="erp-sidebar-footer">
        <button type="button" data-action="open-ai-copilot" class="erp-sidebar-ai-btn" title="مساعد كيان الذكي (AI Copilot)" aria-label="فتح مساعد كيان الذكي">
            <span class="erp-sidebar-ai-icon-box">
                <x-icon name="sparkles" size="18" />
                <span class="ai-pulse-dot"></span>
            </span>
            <div class="erp-sidebar-ai-info">
                <span class="erp-sidebar-ai-title">مساعد كيان الذكي</span>
                <span class="erp-sidebar-ai-badge">استعلامات وتشغيلات لحظية</span>
            </div>
        </button>
    </div>
    @endcan
</aside>
