<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryCategory;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryFoundationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();
        $this->school = School::create(['name' => 'Inventory Test School', 'code' => 'INV']);
    }

    public function test_stores_person_dashboard_is_the_default_dashboard_and_shows_inventory_foundation(): void
    {
        $user = $this->userWithRole('stores-person');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirectToRoute('admin.inventory.index');

        $this->actingAs($user)
            ->get(route('admin.inventory.index'))
            ->assertOk()
            ->assertSee('Stores & Inventory', false)
            ->assertSee('Weighted average')
            ->assertSee(route('admin.inventory.items.index'));
    }

    public function test_stores_person_can_create_school_scoped_categories_items_and_warehouses(): void
    {
        $user = $this->userWithRole('stores-person');
        $this->actingAs($user);

        $this->get(route('admin.inventory.categories.create'))->assertOk()->assertSee('Add Category');
        $this->post(route('admin.inventory.categories.store'), [
            'code' => 'FOOD',
            'name' => 'Food Supplies',
            'description' => 'Kitchen consumables',
            'is_active' => '1',
        ])->assertRedirect(route('admin.inventory.categories.index'));

        $category = InventoryCategory::where('school_id', $this->school->id)->where('code', 'FOOD')->firstOrFail();
        $this->get(route('admin.inventory.categories.index'))->assertOk()->assertSee('Food Supplies');
        $this->get(route('admin.inventory.items.create'))->assertOk()->assertSee('Base unit of measure');

        $this->post(route('admin.inventory.items.store'), [
            'inventory_category_id' => $category->id,
            'code' => 'MEALIE-001',
            'name' => 'Mealie Meal',
            'item_type' => 'consumable',
            'unit_of_measure' => 'bag',
            'description' => 'Meal for kitchen use',
            'is_active' => '1',
        ])->assertRedirect(route('admin.inventory.items.index'));
        $this->get(route('admin.inventory.items.index'))->assertOk()->assertSee('Mealie Meal');

        $this->get(route('admin.inventory.warehouses.create'))->assertOk()->assertSee('Warehouse custodian');
        $this->post(route('admin.inventory.warehouses.store'), [
            'code' => 'MAIN',
            'name' => 'Main Store',
            'location' => 'Administration block',
            'manager_user_id' => $user->id,
            'is_active' => '1',
        ])->assertRedirect(route('admin.inventory.warehouses.index'));
        $this->get(route('admin.inventory.warehouses.index'))->assertOk()->assertSee('Main Store');

        $this->assertDatabaseHas('inventory_items', [
            'school_id' => $this->school->id,
            'inventory_category_id' => $category->id,
            'code' => 'MEALIE-001',
            'item_type' => 'consumable',
            'unit_of_measure' => 'bag',
        ]);
        $this->assertDatabaseHas('inventory_warehouses', [
            'school_id' => $this->school->id,
            'code' => 'MAIN',
            'manager_user_id' => $user->id,
        ]);
    }

    public function test_inventory_catalog_rejects_references_to_another_school(): void
    {
        $user = $this->userWithRole('stores-person');
        $otherSchool = School::create(['name' => 'Other School', 'code' => 'OTH']);
        $otherCategory = InventoryCategory::create([
            'school_id' => $otherSchool->id,
            'code' => 'OTHER',
            'name' => 'Other Category',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('admin.inventory.items.store'), [
                'inventory_category_id' => $otherCategory->id,
                'code' => 'INVALID',
                'name' => 'Other School Item',
                'item_type' => 'consumable',
                'unit_of_measure' => 'each',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('inventory_category_id');

        $this->assertDatabaseMissing('inventory_items', ['code' => 'INVALID']);
    }

    public function test_inventory_permissions_are_not_granted_to_general_staff_roles(): void
    {
        $teacher = $this->userWithRole('teacher');

        $this->actingAs($teacher)
            ->get(route('admin.inventory.index'))
            ->assertForbidden();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['school_id' => $this->school->id]);
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
