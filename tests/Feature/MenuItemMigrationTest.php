<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MenuItemMigrationTest extends TestCase
{
    use RefreshDatabase;

    // AC-01: migration renamed title->label, url->path, added permission_key, dropped required_role
    public function test_menu_items_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('menu_items', 'label'));
        $this->assertTrue(Schema::hasColumn('menu_items', 'path'));
        $this->assertTrue(Schema::hasColumn('menu_items', 'permission_key'));
        $this->assertFalse(Schema::hasColumn('menu_items', 'title'));
        $this->assertFalse(Schema::hasColumn('menu_items', 'url'));
        $this->assertFalse(Schema::hasColumn('menu_items', 'required_role'));
    }

    // AC-02: MenuItem model exposes the expected attributes and a working children() relation
    public function test_menu_item_model_exposes_fields_and_children_relation(): void
    {
        $parent = MenuItem::create([
            'label' => 'Parent',
            'path' => '/parent',
            'icon' => 'icon',
            'permission_key' => 'parent:view',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $child = MenuItem::create([
            'label' => 'Child',
            'parent_id' => $parent->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->assertSame('Parent', $parent->label);
        $this->assertSame('/parent', $parent->path);
        $this->assertSame('icon', $parent->icon);
        $this->assertSame('parent:view', $parent->permission_key);
        $this->assertTrue($parent->is_active);
        $this->assertCount(1, $parent->children);
        $this->assertSame($child->id, $parent->children->first()->id);
    }
}
