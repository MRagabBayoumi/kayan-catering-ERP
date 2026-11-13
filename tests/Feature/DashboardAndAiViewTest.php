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
}
