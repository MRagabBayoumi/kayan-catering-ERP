<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HospitalDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $siteManagerH1;

    protected User $unassignedSiteManager;

    protected Hospital $hospital1;

    protected Hospital $hospital2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
        $this->hospital1 = Hospital::first();
        $this->hospital2 = Hospital::where('id', '!=', $this->hospital1->id)->first() ?? Hospital::create([
            'code' => 'HOSP-TEST-ISO-2',
            'name' => 'مستشفى العزل الثاني',
            'contract_number' => 'CONTR-ISO-02',
            'daily_patient_capacity' => 150,
            'is_active' => true,
        ]);

        $this->siteManagerH1 = User::create([
            'name' => 'مدير موقع مستشفى 1',
            'username' => 'sm_hosp1',
            'email' => 'sm_hosp1@kayan.test',
            'password' => bcrypt('secret123'),
            'role' => User::ROLE_SITE_MANAGER,
            'assigned_hospital_id' => $this->hospital1->id,
            'is_active' => true,
        ]);

        $this->unassignedSiteManager = User::create([
            'name' => 'مدير موقع بدون تعيين',
            'username' => 'sm_unassigned',
            'email' => 'sm_unassigned@kayan.test',
            'password' => bcrypt('secret123'),
            'role' => User::ROLE_SITE_MANAGER,
            'assigned_hospital_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_site_manager_is_strictly_scoped_to_assigned_hospital(): void
    {
        $accessible = $this->siteManagerH1->accessibleHospitals();

        $this->assertCount(1, $accessible);
        $this->assertEquals($this->hospital1->id, $accessible->first()->id);
        $this->assertTrue($this->siteManagerH1->hasAccessToHospital($this->hospital1));
        $this->assertFalse($this->siteManagerH1->hasAccessToHospital($this->hospital2));
    }

    public function test_unassigned_site_manager_receives_empty_collection_and_no_fallback_to_all(): void
    {
        $accessible = $this->unassignedSiteManager->accessibleHospitals();

        $this->assertCount(0, $accessible);
        $this->assertTrue($accessible->isEmpty());
        $this->assertFalse($this->unassignedSiteManager->hasAccessToHospital($this->hospital1));
        $this->assertFalse($this->unassignedSiteManager->hasAccessToHospital($this->hospital2));
    }

    public function test_site_manager_cannot_post_census_for_unassigned_hospital(): void
    {
        $this->actingAs($this->siteManagerH1);

        $response = $this->post(route('site.census.store'), [
            'hospital_id' => $this->hospital2->id,
            'census_date' => now()->toDateString(),
            'protein_choice' => 'meat',
            'carb_choice' => 'plain_rice',
            'veg_choice' => 'cooked_veg',
            'items' => [1 => 50],
        ]);

        $response->assertStatus(403);
    }

    public function test_site_manager_cannot_post_kitchen_issues_for_unassigned_hospital(): void
    {
        $this->actingAs($this->siteManagerH1);

        $response = $this->post(route('site.kitchen-issues.store'), [
            'hospital_id' => $this->hospital2->id,
            'issue_date' => now()->toDateString(),
            'meal_type' => 'lunch',
            'items' => [
                [
                    'product_id' => 1,
                    'quantity_issued' => 10,
                    'unit' => 'kg',
                ],
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_administrator_has_access_to_all_active_hospitals(): void
    {
        $accessible = $this->admin->accessibleHospitals();

        $this->assertGreaterThanOrEqual(2, $accessible->count());
        $this->assertTrue($this->admin->hasAccessToHospital($this->hospital1));
        $this->assertTrue($this->admin->hasAccessToHospital($this->hospital2));
    }
}
