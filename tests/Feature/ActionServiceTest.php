<?php

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Services\ActionService;
use DFiks\UnPerm\Tests\Models\User;
use DFiks\UnPerm\Tests\TestCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActionServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActionService $service;
    private User $user;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ActionService::class);

        $this->user = User::create([
            'name' => 'Regular User',
            'email' => 'user@test.com',
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
        ]);

        $viewAction = Action::create([
            'name' => 'View Permissions',
            'slug' => 'admin.permissions.view',
            'description' => 'View permissions',
        ]);

        $manageAction = Action::create([
            'name' => 'Manage Permissions',
            'slug' => 'admin.permissions.manage',
            'description' => 'Manage permissions',
        ]);

        $this->admin->assignAction($viewAction);
        $this->admin->assignAction($manageAction);
        $this->admin = $this->admin->fresh();
    }

    public function testGetAllRequiresViewPermission(): void
    {
        $this->actingAs($this->user);

        $this->expectException(AuthorizationException::class);
        $this->service->getAll();
    }

    public function testGetAllReturnsAllActions(): void
    {
        Action::create(['name' => 'Test Action 1', 'slug' => 'test.action.1', 'description' => 'Test 1']);
        Action::create(['name' => 'Test Action 2', 'slug' => 'test.action.2', 'description' => 'Test 2']);

        $actions = $this->service->withoutAuthorization()->getAll();

        $this->assertGreaterThanOrEqual(4, $actions->count());
    }

    public function testPaginateReturnsActions(): void
    {
        Action::create(['name' => 'Test Action 1', 'slug' => 'test.action.1', 'description' => 'Test 1']);
        Action::create(['name' => 'Test Action 2', 'slug' => 'test.action.2', 'description' => 'Test 2']);

        $paginated = $this->service->withoutAuthorization()->paginate(10);

        $this->assertGreaterThanOrEqual(4, $paginated->total());
    }

    public function testPaginateWithSearch(): void
    {
        Action::create(['name' => 'Unique Test', 'slug' => 'unique.test', 'description' => 'Unique description']);

        $result = $this->service->withoutAuthorization()->paginate(10, 'unique');

        $this->assertGreaterThanOrEqual(1, $result->total());
    }

    public function testFindReturnsAction(): void
    {
        $action = Action::create(['name' => 'Test Find', 'slug' => 'test.find', 'description' => 'Test']);

        $found = $this->service->withoutAuthorization()->find($action->id);

        $this->assertNotNull($found);
        $this->assertEquals($action->id, $found->id);
    }

    public function testFindBySlugReturnsAction(): void
    {
        Action::create(['name' => 'Test Find By Slug', 'slug' => 'test.findbyslug', 'description' => 'Test']);

        $found = $this->service->withoutAuthorization()->findBySlug('test.findbyslug');

        $this->assertNotNull($found);
        $this->assertEquals('test.findbyslug', $found->slug);
    }

    public function testCreateRequiresManagePermission(): void
    {
        $this->actingAs($this->user);

        $this->expectException(AuthorizationException::class);
        $this->service->create([
            'slug' => 'test.create',
            'description' => 'Test',
        ]);
    }

    public function testCreateCreatesAction(): void
    {
        $action = $this->service->withoutAuthorization()->create([
            'name' => 'Test Create',
            'slug' => 'test.create',
            'description' => 'Test Create',
        ]);

        $this->assertNotNull($action);
        $this->assertEquals('test.create', $action->slug);
        $this->assertDatabaseHas('actions', ['slug' => 'test.create']);
    }

    public function testUpdateRequiresManagePermission(): void
    {
        $this->actingAs($this->user);

        $action = Action::create(['name' => 'Test Update', 'slug' => 'test.update', 'description' => 'Test']);

        $this->expectException(AuthorizationException::class);
        $this->service->update($action, ['description' => 'Updated']);
    }

    public function testUpdateUpdatesAction(): void
    {
        $action = Action::create(['name' => 'Test Update', 'slug' => 'test.update', 'description' => 'Original']);

        $updated = $this->service->withoutAuthorization()->update($action, [
            'description' => 'Updated Description',
        ]);

        $this->assertEquals('Updated Description', $updated->description);
    }

    public function testDeleteRequiresManagePermission(): void
    {
        $this->actingAs($this->user);

        $action = Action::create(['name' => 'Test Delete', 'slug' => 'test.delete', 'description' => 'Test']);

        $this->expectException(AuthorizationException::class);
        $this->service->delete($action);
    }

    public function testDeleteDeletesAction(): void
    {
        $action = Action::create(['name' => 'Test Delete', 'slug' => 'test.delete', 'description' => 'Test']);

        $result = $this->service->withoutAuthorization()->delete($action);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('actions', ['id' => $action->id]);
    }

    public function testSyncSyncsActions(): void
    {
        $actions = [
            'sync.action.1' => null,
            'sync.action.2' => null,
        ];

        $this->service->withoutAuthorization()->sync($actions);

        $this->assertDatabaseHas('actions', ['slug' => 'sync.action.1']);
        $this->assertDatabaseHas('actions', ['slug' => 'sync.action.2']);
    }

    public function testWithoutAuthorizationSkipsPermissionChecks(): void
    {
        $this->actingAs($this->user);

        $actions = $this->service->withoutAuthorization()->getAll();

        $this->assertNotNull($actions);
    }

    public function testWithAuthorizationEnforcesPermissionChecks(): void
    {
        $this->actingAs($this->user);

        $this->expectException(AuthorizationException::class);

        $this->service
            ->withoutAuthorization()
            ->withAuthorization()
            ->getAll();
    }
}
