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
            ['id' => 1,  'pid' => 0,  'name' => '设置',     'sort' => 9, 'controller' => 'admin', 'icon' => 'system-setting',],
            ['id' => 2,  'pid' => 1,  'name' => '菜单管理', 'sort' => 1, 'controller' => 'admin.menu',  'icon' => ''],
            ['id' => 3,  'pid' => 1,  'name' => '角色管理', 'sort' => 2, 'controller' => 'admin.role',  'icon' => ''],
            ['id' => 4,  'pid' => 1,  'name' => '权限管理', 'sort' => 3, 'controller' => 'admin.permission',  'icon' => ''],
            ['id' => 5,  'pid' => 1,  'name' => '用户管理', 'sort' => 4, 'controller' => 'admin.user',  'icon' => ''],

            ['id' => 10, 'pid' => 0,  'name' => '房屋管理', 'sort' => 1, 'controller' => 'house',  'icon' => 'houses'],
            ['id' => 11, 'pid' => 10, 'name' => '房产管理', 'sort' => 1, 'controller' => 'house.property',  'icon' => ''],
            ['id' => 12, 'pid' => 10, 'name' => '房间管理', 'sort' => 2, 'controller' => 'house.number',  'icon' => ''],
            ['id' => 13, 'pid' => 10, 'name' => '未收账单', 'sort' => 4, 'controller' => 'house.uncollected',  'icon' => ''],
            ['id' => 14, 'pid' => 10, 'name' => '到账账单', 'sort' => 5, 'controller' => 'house.collected',  'icon' => ''],
            ['id' => 15, 'pid' => 10, 'name' => '其他支出', 'sort' => 8, 'controller' => 'house.other',  'icon' => ''],

            ['id' => 20, 'pid' => 0, 'name' => '租客管理', 'sort' => 2, 'controller' => 'tenant',  'icon' => 'user-business'],
            ['id' => 21, 'pid' => 20, 'name' => '租客信息', 'sort' => 1, 'controller' => 'tenant.message',  'icon' => ''],
            ['id' => 22, 'pid' => 20, 'name' => '合同管理', 'sort' => 2, 'controller' => 'tenant.contract',  'icon' => ''],
            ['id' => 23, 'pid' => 20, 'name' => '维修申请', 'sort' => 3, 'controller' => 'tenant.maintain',  'icon' => ''],

            ['id' => 30, 'pid' => 0,  'name' => '水电管理', 'sort' => 3, 'controller' => 'we',  'icon' => 'bill'],
            ['id' => 31, 'pid' => 30, 'name' => '水电总表', 'sort' => 1, 'controller' => 'we.meter',  'icon' => ''],
            ['id' => 32, 'pid' => 30, 'name' => '水费账单', 'sort' => 2, 'controller' => 'we.water',  'icon' => ''],
            ['id' => 33, 'pid' => 30, 'name' => '电费账单', 'sort' => 3, 'controller' => 'we.electricity',  'icon' => ''],

            ['id' => 40, 'pid' => 0,  'name' => '我的账本', 'sort' => 4, 'controller' => 'bill',  'icon' => 'chart-line-data-1'],
            ['id' => 41, 'pid' => 40, 'name' => '房产报表', 'sort' => 1, 'controller' => 'bill.report',  'icon' => ''],
            ['id' => 42, 'pid' => 40, 'name' => '年度报表', 'sort' => 2, 'controller' => 'bill.annual',  'icon' => '']
        ])->save();
    }
}
