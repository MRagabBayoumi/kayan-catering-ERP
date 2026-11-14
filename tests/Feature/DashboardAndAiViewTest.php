<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAndAiViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_successfully_for_authenticated_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $hospital = Hospital::create([
            'code' => 'HOSP-TEST',
            'name' => 'مستشفى تجريبي',
            'daily_patient_capacity' => 150,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('منظومة كيان لإعاشة وتغذية المستشفيات');
        $response->assertSee('Kayan AI Copilot');
        $response->assertSee('erp-sidebar-footer');
        $response->assertDontSee('ai-launcher-btn');
    }

    public function test_ai_copilot_endpoint_returns_json_response(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->postJson('/ai/ask', [
            'prompt' => 'عندي قد ايه لحمه',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'response' => [
                'intent',
                'headline',
                'message',
                'data',
                'recommendations',
            ],
        ]);
    }

    public function test_home_odoo_apps_hub_renders_all_modules_and_subtabs(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get('/home');

        $response->assertStatus(200);
        $response->assertSee('المخازن');
        $response->assertSee('المقننات والتكاليف');
        $response->assertSee('المستشفيات');
        $response->assertSee('المناقصات');
        $response->assertSee('المشتريات');
        $response->assertSee('الحسابات والمالية');
        $response->assertSee('التقفيل للمالك');
        $response->assertSee('مساعد كيان (AI)');
        $response->assertSee('المستخدمين');
        $response->assertSee('إعدادات النظام');

        // Isolation test: Storekeeper can only see warehouse and NOT finance, costing, or closing
        $storekeeper = User::factory()->create(['role' => User::ROLE_STOREKEEPER]);
        $storekeeperHome = $this->actingAs($storekeeper)->get('/home');
        $storekeeperHome->assertStatus(200);
        $storekeeperHome->assertSee('data-app-name="المخازن والتشغيلات"', false);
        $storekeeperHome->assertDontSee('data-app-name="التقفيل واليومية للمالك"', false);
        $storekeeperHome->assertDontSee('data-app-name="الحسابات والمالية"', false);
        $storekeeperHome->assertDontSee('data-app-name="المقننات والتكاليف"', false);
        $storekeeperHome->assertDontSee('data-app-name="المناقصات والعطاءات"', false);
        $storekeeperHome->assertDontSee('data-app-name="إعدادات المنظومة"', false);

        // Test entering warehouse module and seeing sub-navigation tabs
        $warehouseResponse = $this->actingAs($admin)->get('/warehouse');
        $warehouseResponse->assertStatus(200);
        $warehouseResponse->assertSee('erp-tab-nav-card');
        $warehouseResponse->assertSee('تشغيلات الشراء والـ FIFO');
        $warehouseResponse->assertSee('أذون التحميل والصرف (السيارات)');
        $warehouseResponse->assertSee('الجرد الأعمى (Blind Audit)');
        $warehouseResponse->assertSee('تسويات رحلات المناديب ومخرجات الـ ERP');
    }

    public function test_settings_can_be_updated_via_put_request(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->put('/settings', [
            'company_name' => 'شركة كيان لإعاشة المستشفيات',
            'company_phone' => '01000000000',
            'program_status' => 'active',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('status', 'success');
    }
}
