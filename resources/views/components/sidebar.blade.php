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
            <div class="erp-nav-section-items" style="display: block;">
                <a href="{{ route('dashboard') }}" class="erp-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="لوحة التحكم والمؤشرات">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="layout-dashboard" size="18" /></span>
                        <span class="erp-nav-label">لوحة التحكم العامة</span>
                    </div>
                </a>
                <button type="button" onclick="document.getElementById('aiLauncherBtn')?.click()" class="erp-nav-item w-full text-right" style="background: rgba(59, 130, 246, 0.08); border-right: 3px solid #3b82f6;">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon text-blue-500">🤖</span>
                        <span class="erp-nav-label font-bold text-blue-600 dark:text-blue-400">مساعد كيان الذكي (AI)</span>
                    </div>
                </button>
            </div>
        </div>

        <!-- 1. Warehouse Department (إدارة المخازن والـ FIFO) -->
        <div class="erp-nav-section is-open" data-section="warehouse">
            <button type="button" class="erp-nav-section-header" aria-expanded="true">
                <span class="erp-nav-section-title">📦 1. إدارة المخازن والـ FIFO</span>
                <span class="erp-nav-section-arrow">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
            </button>
            <div class="erp-nav-section-items" style="display: block;">
                <a href="{{ url('/') }}" class="erp-nav-item" title="أرصدة وتشغيلات الباتشات">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="layers" size="16" /></span>
                        <span class="erp-nav-label">تشغيلات الشراء (Batches FIFO)</span>
                    </div>
                </a>
                <a href="{{ url('/') }}" class="erp-nav-item" title="أذون التحميل والصرف لسيارات المناديب">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="truck" size="16" /></span>
                        <span class="erp-nav-label">أذون التحميل والصرف (السيارات)</span>
                    </div>
                </a>
                <a href="{{ url('/') }}" class="erp-nav-item" title="الجرد اليومي الأعمى لأمين المخزن">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="check-square" size="16" /></span>
                        <span class="erp-nav-label">الجرد اليومي الأعمى (Blind Audit)</span>
                    </div>
                </a>
                <a href="{{ url('/') }}" class="erp-nav-item" title="تسوية عهدة السائق ومخرجات الـ ERP">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="file-text" size="16" /></span>
                        <span class="erp-nav-label">تسوية عهد المناديب (محاسب المخزن)</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- 2. Costing Department (إدارة التكاليف والمقننات) -->
        <div class="erp-nav-section is-open" data-section="costing">
            <button type="button" class="erp-nav-section-header" aria-expanded="true">
                <span class="erp-nav-section-title">⚖️ 2. إدارة التكاليف والمقننات</span>
                <span class="erp-nav-section-arrow">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
            </button>
            <div class="erp-nav-section-items" style="display: block;">
                <a href="{{ url('/') }}" class="erp-nav-item" title="المقننات الغذائية القياسية">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="book-open" size="16" /></span>
                        <span class="erp-nav-label">المقننات القياسية للوجبات (BOM)</span>
                    </div>
                </a>
                <a href="{{ url('/') }}" class="erp-nav-item" title="متابعة استهلاك المطبخ الفعلي">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="activity" size="16" /></span>
                        <span class="erp-nav-label">متابعة الاستهلاك الفعلي للمطبخ</span>
                    </div>
                </a>
                <a href="{{ url('/') }}" class="erp-nav-item" title="كشف انحرافات المقنن والهدر المالي">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="alert-circle" size="16" /></span>
                        <span class="erp-nav-label">كشف انحرافات المقنن (Variance)</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- 3. Sales & Tenders Department (إدارة المبيعات والمناقصات) -->
        <div class="erp-nav-section is-open" data-section="sales">
            <button type="button" class="erp-nav-section-header" aria-expanded="true">
                <span class="erp-nav-section-title">💼 3. إدارة المبيعات والمناقصات</span>
                <span class="erp-nav-section-arrow">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
            </button>
            <div class="erp-nav-section-items" style="display: block;">
                <a href="{{ url('/') }}" class="erp-nav-item" title="المستشفيات والجهات المتعاقدة">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="shield" size="16" /></span>
                        <span class="erp-nav-label">المستشفيات والتعاقدات</span>
                    </div>
                </a>
                <a href="{{ url('/') }}" class="erp-nav-item" title="سجل المناقصات وكراسات الشروط">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="file-text" size="16" /></span>
                        <span class="erp-nav-label">المناقصات والعطاءات (Tenders)</span>
                    </div>
                </a>
                <a href="{{ url('/') }}" class="erp-nav-item" title="أرشيف أسعار المنافسين للعام القادم">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="archive" size="16" /></span>
                        <span class="erp-nav-label">أرشيف أسعار المنافسين</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- 4. Procurement & Finance Section -->
        <div class="erp-nav-section" data-section="finance">
            <button type="button" class="erp-nav-section-header" aria-expanded="false">
                <span class="erp-nav-section-title">💰 4. المشتريات والمالية</span>
                <span class="erp-nav-section-arrow">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
            </button>
            <div class="erp-nav-section-items">
                <a href="{{ url('/') }}" class="erp-nav-item" title="حسابات الموردين وجدولة السداد">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="credit-card" size="16" /></span>
                        <span class="erp-nav-label">حسابات الموردين والآجل</span>
                    </div>
                </a>
                <a href="{{ url('/') }}" class="erp-nav-item" title="الخزينة والبنوك وعهد المواقع">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="dollar-sign" size="16" /></span>
                        <span class="erp-nav-label">الخزينة وعهد المواقع النقدية</span>
                    </div>
                </a>
                <a href="{{ url('/') }}" class="erp-nav-item" title="التقفيل اليومي لصاحب العمل">
                    <div class="erp-nav-content">
                        <span class="erp-nav-icon"><x-icon name="pie-chart" size="16" /></span>
                        <span class="erp-nav-label">اليومية والتقفيل لصاحب العمل</span>
                    </div>
                </a>
            </div>
        </div>
    </nav>
</aside>
