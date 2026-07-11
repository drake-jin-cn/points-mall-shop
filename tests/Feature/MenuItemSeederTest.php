<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use Database\Seeders\MenuItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuItemSeederTest extends TestCase
{
    use RefreshDatabase;

    // AC-04: seeded tree matches the design spec exactly, including permission_key values
    public function test_seeder_produces_expected_tree(): void
    {
        (new MenuItemSeeder())->run();

        $topLevel = MenuItem::whereNull('parent_id')->orderBy('sort_order')->get();
        $this->assertSame(
            ['Dashboard', '考勤打卡', '积分商城', '数据报表', '系统设置'],
            $topLevel->pluck('label')->all(),
        );
        $this->assertSame(
            ['dashboard:view', 'attendance:view', 'shop:view', 'data:view', null],
            $topLevel->pluck('permission_key')->all(),
        );

        $settings = $topLevel->firstWhere('label', '系统设置');
        $children = $settings->children()->get();
        $this->assertSame(['菜单管理', '员工管理'], $children->pluck('label')->all());
        $this->assertSame(['admin:menu:view', 'admin:employee:view'], $children->pluck('permission_key')->all());
    }

    // AC-03: re-running the seeder does not duplicate rows
    public function test_seeder_is_idempotent(): void
    {
        (new MenuItemSeeder())->run();
        (new MenuItemSeeder())->run();

        $this->assertSame(7, MenuItem::count());
    }
}
