<?php

namespace Kiamars\RbacArchitect\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kiamars\RbacArchitect\Models\Role;
use Kiamars\RbacArchitect\Models\Permission;
use Tests\TestCase;
use App\Models\User;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test roles and permissions
        Role::create(['name' => 'admin', 'description' => 'Administrator']);
        Role::create(['name' => 'manager', 'description' => 'Manager']);

        Permission::create(['name' => 'edit-settings', 'description' => 'Edit settings']);
        Permission::create(['name' => 'view-reports', 'description' => 'View reports']);
    }

    /** @test */
    public function it_can_assign_a_role_to_user()
    {
        $user = User::factory()->create();

        $user->assignRole('admin');

        $this->assertTrue($user->hasRole('admin'));
    }

    /** @test */
    public function it_can_assign_a_context_specific_role()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $user->assignRole('manager', $project);

        $this->assertTrue($user->hasRole('manager', $project));
        $this->assertFalse($user->hasRole('manager')); // Global check should fail
    }

    /** @test */
    public function it_can_check_permissions_through_roles()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'admin')->first();
        $permission = Permission::where('name', 'edit-settings')->first();

        $role->permissions()->attach($permission);
        $user->assignRole('admin');

        $this->assertTrue($user->hasPermissionTo('edit-settings'));
    }

    /** @test */
    public function it_can_assign_direct_permissions()
    {
        $user = User::factory()->create();

        $user->assignPermission('view-reports');

        $this->assertTrue($user->hasPermissionTo('view-reports'));
    }

    /** @test */
    public function it_respects_temporal_permissions()
    {
        $user = User::factory()->create();

        // Permission that hasn't activated yet
        $user->assignPermission(
            'future-permission',
            null,
            now()->addDays(1),
            now()->addDays(30)
        );

        $this->assertFalse($user->hasPermissionTo('future-permission'));
    }

    /** @test */
    public function it_respects_expired_permissions()
    {
        $user = User::factory()->create();

        // Permission that already expired
        $user->assignPermission(
            'expired-permission',
            null,
            now()->subDays(30),
            now()->subDays(1)
        );

        $this->assertFalse($user->hasPermissionTo('expired-permission'));
    }

    /** @test */
    public function root_user_has_all_permissions()
    {
        $user = User::factory()->create(['is_root' => true]);

        $this->assertTrue($user->isRoot());
        $this->assertTrue($user->hasPermissionTo('any-permission'));
        $this->assertTrue($user->hasPermissionTo('another-permission'));
    }

    /** @test */
    public function it_can_revoke_roles()
    {
        $user = User::factory()->create();

        $user->assignRole('admin');
        $this->assertTrue($user->hasRole('admin'));

        $user->revokeRole('admin');
        $this->assertFalse($user->hasRole('admin'));
    }

    /** @test */
    public function it_can_revoke_permissions()
    {
        $user = User::factory()->create();

        $user->assignPermission('edit-settings');
        $this->assertTrue($user->hasPermissionTo('edit-settings'));

        $user->revokePermission('edit-settings');
        $this->assertFalse($user->hasPermissionTo('edit-settings'));
    }

    /** @test */
    public function it_can_check_multiple_permissions()
    {
        $user = User::factory()->create();

        $user->assignPermission('edit-settings');
        $user->assignPermission('view-reports');

        $this->assertTrue($user->hasAllPermissions(['edit-settings', 'view-reports']));
        $this->assertTrue($user->hasAnyPermission(['edit-settings', 'non-existent']));
        $this->assertFalse($user->hasAllPermissions(['edit-settings', 'non-existent']));
    }
}