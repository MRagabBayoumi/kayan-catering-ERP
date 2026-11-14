<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentIsolationNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_clean_odoo_apps_grid_without_sidebar(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get('/');

        $response->assertStatus(200);
        $response->assertSee('data-page="home-apps"', false);
        $response->assertSee('odoo-apps-grid', false);
        // Ensure sidebar is hidden on Home screen
        $response->assertDontSee('erp-sidebar-module-banner');
    }

    public function test_warehouse_department_isolates_navigation_to_warehouse_subsections_only(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get('/warehouse');

        $response->assertStatus(200);
        // Module banner & back button
        $response->assertSee('إدارة المخازن والتشغيلات (FIFO)');
        $response->assertSee('العودة لكل التطبيقات');

        // Subsections inside Warehouse
        $response->assertSee('نظرة عامة');
        $response->assertSee('نظرة عامة على المخازن');
        $response->assertSee('حركة المخزون والتشغيلات');
        $response->assertSee('تشغيلات الشراء (Batches FIFO)');
        $response->assertSee('الصلاحيات وتنبيهات FEFO');
        $response->assertSee('الجرد اليومي الأعمى (Blind Audit)');
        $response->assertSee('التوزيع والتسويات');
        $response->assertSee('أذون التحميل والصرف (السيارات)');
        $response->assertSee('تسوية عهد المناديب (محاسب المخزن)');

        // Strictly verify other departments are NOT shown in navigation
        $response->assertDontSee('إدارة التكاليف والمقننات (BOM)');
        $response->assertDontSee('المقننات والطلبيات القياسية');
        $response->assertDontSee('إدارة الحسابات والمالية');
        $response->assertDontSee('الخزينة والعهد النقدية');
        $response->assertDontSee('إدارة المشتريات والتوريدات');
    }

    public function test_costing_department_isolates_navigation_to_costing_subsections_only(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get('/costing/recipes');

        $response->assertStatus(200);
        // Module banner & back button
        $response->assertSee('إدارة التكاليف والمقننات (BOM)');
        $response->assertSee('العودة لكل التطبيقات');

        // Subsections inside Costing
        $response->assertSee('نظرة عامة');
        $response->assertSee('نظرة عامة على التكاليف');
        $response->assertSee('المقننات والطلبيات القياسية');
        $response->assertSee('المقننات القياسية للوجبات (BOM)');
        $response->assertSee('حساب طلبيات المطبخ (المقنن)');
        $response->assertSee('الرقابة وتحليل الانحرافات والهدر');
        $response->assertSee('كشف انحرافات المقنن (Variance)');
        $response->assertSee('مطابقة الموقع وكشف الهدر/العجز');

        // Strictly verify warehouse and finance are NOT shown in navigation
        $response->assertDontSee('إدارة المخازن والتشغيلات (FIFO)');
        $response->assertDontSee('حركة المخزون والتشغيلات');
        $response->assertDontSee('إدارة الحسابات والمالية');
    }

    public function test_finance_department_isolates_navigation_to_finance_subsections_only(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get('/finance/suppliers');

        $response->assertStatus(200);
        // Module banner & back button
        $response->assertSee('إدارة الحسابات والمالية');
        $response->assertSee('العودة لكل التطبيقات');

        // Subsections inside Finance
        $response->assertSee('نظرة عامة');
        $response->assertSee('نظرة عامة على المالية');
        $response->assertSee('الموردين والمديونيات');
        $response->assertSee('الخزينة والعهد النقدية');
        $response->assertSee('الحسابات العامة والتقفيل');

        // Strictly verify warehouse and costing are NOT shown in navigation
        $response->assertDontSee('إدارة المخازن والتشغيلات (FIFO)');
        $response->assertDontSee('حركة المخزون والتشغيلات');
        $response->assertDontSee('إدارة التكاليف والمقننات (BOM)');
    }
}
