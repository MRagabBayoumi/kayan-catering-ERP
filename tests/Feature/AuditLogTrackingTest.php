<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
    }

    public function test_updating_auditable_model_records_audit_log_with_changes(): void
    {
        $this->actingAs($this->admin);

        $setting = CompanySetting::first();
        $oldName = $setting->company_name;

        $setting->update([
            'company_name' => 'Kayan Global Catering New Name',
        ]);

        $log = AuditLog::where('auditable_type', CompanySetting::class)
            ->where('auditable_id', $setting->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->admin->id, $log->user_id);
        $this->assertEquals($oldName, $log->old_values['company_name'] ?? null);
        $this->assertEquals('Kayan Global Catering New Name', $log->new_values['company_name'] ?? null);
    }

    public function test_creating_user_records_audit_log(): void
    {
        $this->actingAs($this->admin);

        $user = User::create([
            'name' => 'مدير مراقبة جودة جديد',
            'username' => 'qa_audited_user',
            'email' => 'qa_audit@kayan.test',
            'password' => bcrypt('secret123'),
            'role' => User::ROLE_STOREKEEPER,
            'is_active' => true,
        ]);

        $log = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->admin->id, $log->user_id);
        $this->assertEquals('qa_audit@kayan.test', $log->new_values['email'] ?? null);
    }
}
