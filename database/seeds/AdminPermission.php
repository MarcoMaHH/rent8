<?php

use think\migration\Seeder;

class AdminPermission extends Seeder
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
        $this->table('admin_permission')->insert([
            // 管理员
            ['id' => 1, 'admin_role_id' => 1, 'controller' => '*', 'action' => '*'],
            // 体验者
            ['id' => 2, 'admin_role_id' => 2, 'controller' => 'index', 'action' => 'index,queryHouse,login,logout,echar'],
            ['id' => 3, 'admin_role_id' => 2, 'controller' => 'house', 'action' => 'index'],
            ['id' => 4, 'admin_role_id' => 2, 'controller' => 'house.property', 'action' => 'index,query,sort'],
            ['id' => 5, 'admin_role_id' => 2, 'controller' => 'house.number', 'action' => 'index,query,queryNumberId,contract'],
            ['id' => 6, 'admin_role_id' => 2, 'controller' => 'house.uncollected', 'action' => 'index,queryReadingTime,query,queryHistory,centralized'],
            ['id' => 7, 'admin_role_id' => 2, 'controller' => 'house.collected', 'action' => 'index,query,sum'],
            ['id' => 8, 'admin_role_id' => 2, 'controller' => 'house.tenant', 'action' => 'index,query'],
            ['id' => 9, 'admin_role_id' => 2, 'controller' => 'house.other', 'action' => 'index,query'],

            // 普通会员
            ['id' => 41, 'admin_role_id' => 3, 'controller' => 'index', 'action' => '*'],
            ['id' => 42, 'admin_role_id' => 3, 'controller' => 'house', 'action' => 'index'],
            ['id' => 43, 'admin_role_id' => 3, 'controller' => 'house.property', 'action' => '*'],
            ['id' => 44, 'admin_role_id' => 3, 'controller' => 'house.number', 'action' => '*'],
            ['id' => 45, 'admin_role_id' => 3, 'controller' => 'house.uncollected', 'action' => '*'],
            ['id' => 46, 'admin_role_id' => 3, 'controller' => 'house.collected', 'action' => '*'],
            ['id' => 47, 'admin_role_id' => 3, 'controller' => 'house.tenant', 'action' => '*'],
            ['id' => 48, 'admin_role_id' => 3, 'controller' => 'house.other', 'action' => '*'],
            ['id' => 49, 'admin_role_id' => 3, 'controller' => 'we', 'action' => 'index'],
            ['id' => 50, 'admin_role_id' => 3, 'controller' => 'we.meter', 'action' => '*'],
            ['id' => 51, 'admin_role_id' => 3, 'controller' => 'we.water', 'action' => '*'],
            ['id' => 52, 'admin_role_id' => 3, 'controller' => 'we.electricity', 'action' => '*'],
            ['id' => 53, 'admin_role_id' => 3, 'controller' => 'bill', 'action' => 'index'],
            ['id' => 54, 'admin_role_id' => 3, 'controller' => 'bill.annual', 'action' => '*'],
            ['id' => 55, 'admin_role_id' => 3, 'controller' => 'bill.report', 'action' => '*'],
        ])->save();
    }
}
