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
        $response->assertSee('ai-launcher-btn');
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
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertStatus(200);
        $response->assertSee('إدارة المخازن والتشغيلات (FIFO)');
        $response->assertSee('إدارة التكاليف والمقننات (BOM)');
        $response->assertSee('المستشفيات والتعاقدات');
        $response->assertSee('المناقصات والعطاءات (Tenders)');
        $response->assertSee('إدارة المشتريات والتوريدات');
        $response->assertSee('الحسابات والمالية');
        $response->assertSee('التقفيل واليومية للمالك');
        $response->assertSee('مساعد كيان الذكي (AI Copilot)');
        $response->assertSee('المستخدمين ومصفوفة الصلاحيات');
        $response->assertSee('إعدادات المنظومة والشركة');

        // Test entering warehouse module and seeing sub-navigation tabs
        $warehouseResponse = $this->actingAs($user)->get('/warehouse');
        $warehouseResponse->assertStatus(200);
        $warehouseResponse->assertSee('erp-tab-nav-card');
        $warehouseResponse->assertSee('تشغيلات الشراء والـ FIFO');
        $warehouseResponse->assertSee('أذون التحميل والصرف (السيارات)');
        $warehouseResponse->assertSee('الجرد الأعمى (Blind Audit)');
        $warehouseResponse->assertSee('تسويات رحلات المناديب ومخرجات الـ ERP');
    }
}
