<?php

use think\migration\Seeder;

class AdminMenu extends Seeder
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run(): void
    {
        $this->table('admin_menu')->insert([
            ['id' => 1, 'pid' => 0, 'name' => '设置', 'icon' => 'system-setting',
             'controller' => 'admin', 'sort' => 99],
            ['id' => 2, 'pid' => 1, 'name' => '菜单管理', 'icon' => '',
             'controller' => 'admin.menu', 'sort' => 1],
            ['id' => 3, 'pid' => 1, 'name' => '角色管理', 'icon' => '',
             'controller' => 'admin.role', 'sort' => 2],
            ['id' => 4, 'pid' => 1, 'name' => '权限管理', 'icon' => '',
             'controller' => 'admin.permission', 'sort' => 3],
            ['id' => 5, 'pid' => 1, 'name' => '用户管理', 'icon' => '',
             'controller' => 'admin.user', 'sort' => 4],
            ['id' => 10, 'pid' => 0, 'name' => '房屋管理', 'icon' => 'houses',
             'controller' => 'house', 'sort' => 1],
            ['id' => 11, 'pid' => 10, 'name' => '房产管理', 'icon' => '',
             'controller' => 'house.property', 'sort' => 1],
            ['id' => 12, 'pid' => 10, 'name' => '房间管理', 'icon' => '',
             'controller' => 'house.number', 'sort' => 2],
            ['id' => 13, 'pid' => 10, 'name' => '未收账单', 'icon' => '',
             'controller' => 'house.uncollected', 'sort' => 3],
            ['id' => 14, 'pid' => 10, 'name' => '到账账单', 'icon' => '',
             'controller' => 'house.collected', 'sort' => 4],
            ['id' => 15, 'pid' => 10, 'name' => '租客档案', 'icon' => '',
             'controller' => 'house.tenant', 'sort' => 5],
            ['id' => 16, 'pid' => 10, 'name' => '合同管理', 'icon' => '',
             'controller' => 'house.other', 'sort' => 6],
            ['id' => 17, 'pid' => 10, 'name' => '其他支出', 'icon' => '',
             'controller' => 'house.contract', 'sort' => 7],
            ['id' => 20, 'pid' => 0, 'name' => '水电管理', 'icon' => 'bill',
             'controller' => 'we', 'sort' => 2],
            ['id' => 21, 'pid' => 20, 'name' => '水电总表', 'icon' => '',
             'controller' => 'we.meter', 'sort' => 1],
            ['id' => 22, 'pid' => 20, 'name' => '水费账单', 'icon' => '',
             'controller' => 'we.water', 'sort' => 2],
            ['id' => 23, 'pid' => 20, 'name' => '电费账单', 'icon' => '',
             'controller' => 'we.electricity', 'sort' => 3],
            ['id' => 30, 'pid' => 0, 'name' => '我的账本', 'icon' => 'chart-line-data-1',
             'controller' => 'bill', 'sort' => 3],
            ['id' => 31, 'pid' => 30, 'name' => '房产报表', 'icon' => '',
             'controller' => 'bill.report', 'sort' => 1],
            ['id' => 32, 'pid' => 30, 'name' => '年度报表', 'icon' => '',
             'controller' => 'bill.annual', 'sort' => 2]
        ])->save();
    }
}
