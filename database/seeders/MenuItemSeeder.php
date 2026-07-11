<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Idempotent: keyed by (label, parent_id) so re-running does not duplicate rows.
     */
    public function run(): void
    {
        $topLevel = [
            ['label' => 'Dashboard', 'path' => '/dashboard', 'icon' => 'dashboard', 'permission_key' => 'dashboard:view', 'sort_order' => 1],
            ['label' => '考勤打卡', 'path' => '/attendance', 'icon' => 'clock', 'permission_key' => 'attendance:view', 'sort_order' => 2],
            ['label' => '积分商城', 'path' => '/shop', 'icon' => 'shop', 'permission_key' => 'shop:view', 'sort_order' => 3],
            ['label' => '数据报表', 'path' => '/data', 'icon' => 'chart', 'permission_key' => 'data:view', 'sort_order' => 4],
        ];

        foreach ($topLevel as $item) {
            $this->seedItem($item, null);
        }

        $settings = $this->seedItem([
            'label' => '系统设置',
            'path' => null,
            'icon' => 'settings',
            'permission_key' => null,
            'sort_order' => 5,
        ], null);

        $settingsChildren = [
            ['label' => '菜单管理', 'path' => '/admin/menus', 'icon' => 'menu', 'permission_key' => 'admin:menu:view', 'sort_order' => 1],
            ['label' => '员工管理', 'path' => '/admin/employees', 'icon' => 'users', 'permission_key' => 'admin:employee:view', 'sort_order' => 2],
        ];

        foreach ($settingsChildren as $item) {
            $this->seedItem($item, $settings->id);
        }
    }

    private function seedItem(array $attributes, ?int $parentId): MenuItem
    {
        return MenuItem::query()->updateOrCreate(
            ['label' => $attributes['label'], 'parent_id' => $parentId],
            [
                'path' => $attributes['path'],
                'icon' => $attributes['icon'],
                'permission_key' => $attributes['permission_key'],
                'sort_order' => $attributes['sort_order'],
                'is_active' => true,
            ],
        );
    }
}
