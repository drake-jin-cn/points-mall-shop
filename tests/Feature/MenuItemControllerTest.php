<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuItemControllerTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = 'INTERNAL_API_KEY';
    private const KEY = 'test-internal-key-for-tests';

    // AC-05: missing/invalid INTERNAL_API_KEY → 401 shop-4013, for all four routes
    public function test_index_missing_api_key_returns_401(): void
    {
        $this->getJson('/internal/admin/menus')
            ->assertStatus(401)
            ->assertJsonPath('code', 'shop-4013');
    }

    public function test_store_missing_api_key_returns_401(): void
    {
        $this->postJson('/internal/admin/menus', ['label' => 'Foo'])
            ->assertStatus(401)
            ->assertJsonPath('code', 'shop-4013');
    }

    public function test_update_missing_api_key_returns_401(): void
    {
        $item = MenuItem::create(['label' => 'Foo', 'sort_order' => 1, 'is_active' => true]);

        $this->putJson("/internal/admin/menus/{$item->id}", ['label' => 'Bar'])
            ->assertStatus(401)
            ->assertJsonPath('code', 'shop-4013');
    }

    public function test_destroy_missing_api_key_returns_401(): void
    {
        $item = MenuItem::create(['label' => 'Foo', 'sort_order' => 1, 'is_active' => true]);

        $this->deleteJson("/internal/admin/menus/{$item->id}")
            ->assertStatus(401)
            ->assertJsonPath('code', 'shop-4013');
    }

    public function test_invalid_api_key_returns_401(): void
    {
        $this->getJson('/internal/admin/menus', [self::HEADER => 'wrong-key'])
            ->assertStatus(401)
            ->assertJsonPath('code', 'shop-4013');
    }

    // AC-06: GET returns full tree, nested children, sorted by sort_order
    public function test_index_returns_nested_tree_sorted_by_sort_order(): void
    {
        $settings = MenuItem::create(['label' => '系统设置', 'sort_order' => 2, 'is_active' => true]);
        MenuItem::create(['label' => 'Dashboard', 'path' => '/dashboard', 'permission_key' => 'dashboard:view', 'sort_order' => 1, 'is_active' => true]);
        MenuItem::create(['label' => '员工管理', 'parent_id' => $settings->id, 'permission_key' => 'admin:employee:view', 'sort_order' => 2, 'is_active' => true]);
        MenuItem::create(['label' => '菜单管理', 'parent_id' => $settings->id, 'permission_key' => 'admin:menu:view', 'sort_order' => 1, 'is_active' => true]);

        $response = $this->getJson('/internal/admin/menus', [self::HEADER => self::KEY])
            ->assertStatus(200)
            ->assertJsonPath('code', 'OK');

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertSame('Dashboard', $data[0]['label']);
        $this->assertSame('系统设置', $data[1]['label']);
        $this->assertCount(2, $data[1]['children']);
        $this->assertSame('菜单管理', $data[1]['children'][0]['label']);
        $this->assertSame('员工管理', $data[1]['children'][1]['label']);
    }

    // AC-07: POST creates and returns 201
    public function test_store_creates_menu_item(): void
    {
        $response = $this->postJson('/internal/admin/menus', [
            'label' => 'Dashboard',
            'path' => '/dashboard',
            'icon' => 'dashboard',
            'permission_key' => 'dashboard:view',
            'sort_order' => 1,
            'is_active' => true,
        ], [self::HEADER => self::KEY])
            ->assertStatus(201)
            ->assertJsonPath('code', 'OK')
            ->assertJsonPath('data.label', 'Dashboard');

        $this->assertDatabaseHas('menu_items', ['label' => 'Dashboard', 'permission_key' => 'dashboard:view']);
    }

    // AC-08: POST missing required field (label) → 400 shop-4010
    public function test_store_missing_label_returns_400(): void
    {
        $this->postJson('/internal/admin/menus', ['path' => '/x'], [self::HEADER => self::KEY])
            ->assertStatus(400)
            ->assertJsonPath('code', 'shop-4010');
    }

    // AC-09: PUT updates fields
    public function test_update_updates_menu_item(): void
    {
        $item = MenuItem::create(['label' => 'Old', 'sort_order' => 1, 'is_active' => true]);

        $this->putJson("/internal/admin/menus/{$item->id}", ['label' => 'New'], [self::HEADER => self::KEY])
            ->assertStatus(200)
            ->assertJsonPath('data.label', 'New');

        $this->assertDatabaseHas('menu_items', ['id' => $item->id, 'label' => 'New']);
    }

    // AC-10: PUT non-existent id → 404 shop-4012
    public function test_update_nonexistent_id_returns_404(): void
    {
        $this->putJson('/internal/admin/menus/999999', ['label' => 'New'], [self::HEADER => self::KEY])
            ->assertStatus(404)
            ->assertJsonPath('code', 'shop-4012');
    }

    // AC-11: DELETE leaf item succeeds
    public function test_destroy_leaf_item_succeeds(): void
    {
        $item = MenuItem::create(['label' => 'Leaf', 'sort_order' => 1, 'is_active' => true]);

        $this->deleteJson("/internal/admin/menus/{$item->id}", [], [self::HEADER => self::KEY])
            ->assertStatus(200)
            ->assertJsonPath('code', 'OK');

        $this->assertDatabaseMissing('menu_items', ['id' => $item->id]);
    }

    // AC-12: DELETE item with children → 409 shop-4011, nothing deleted
    public function test_destroy_item_with_children_returns_409(): void
    {
        $parent = MenuItem::create(['label' => 'Parent', 'sort_order' => 1, 'is_active' => true]);
        $child = MenuItem::create(['label' => 'Child', 'parent_id' => $parent->id, 'sort_order' => 1, 'is_active' => true]);

        $this->deleteJson("/internal/admin/menus/{$parent->id}", [], [self::HEADER => self::KEY])
            ->assertStatus(409)
            ->assertJsonPath('code', 'shop-4011');

        $this->assertDatabaseHas('menu_items', ['id' => $parent->id]);
        $this->assertDatabaseHas('menu_items', ['id' => $child->id]);
    }

    // AC-13: DELETE non-existent id → 404 shop-4012
    public function test_destroy_nonexistent_id_returns_404(): void
    {
        $this->deleteJson('/internal/admin/menus/999999', [], [self::HEADER => self::KEY])
            ->assertStatus(404)
            ->assertJsonPath('code', 'shop-4012');
    }
}
