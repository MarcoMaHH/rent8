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
            // 普通会员
            ['id' => 11, 'admin_role_id' => 2, 'controller' => 'index', 'action' => '*'],
            ['id' => 12, 'admin_role_id' => 2, 'controller' => 'house', 'action' => 'index'],
            ['id' => 13, 'admin_role_id' => 2, 'controller' => 'house.property', 'action' => '*'],
            ['id' => 14, 'admin_role_id' => 2, 'controller' => 'house.number', 'action' => '*'],
            ['id' => 15, 'admin_role_id' => 2, 'controller' => 'house.uncollected', 'action' => '*'],
            ['id' => 16, 'admin_role_id' => 2, 'controller' => 'house.collected', 'action' => '*'],
            ['id' => 17, 'admin_role_id' => 2, 'controller' => 'house.tenant', 'action' => '*'],
            ['id' => 18, 'admin_role_id' => 2, 'controller' => 'house.other', 'action' => '*'],
            ['id' => 19, 'admin_role_id' => 2, 'controller' => 'we', 'action' => 'index'],
            ['id' => 20, 'admin_role_id' => 2, 'controller' => 'we.meter', 'action' => '*'],
            ['id' => 21, 'admin_role_id' => 2, 'controller' => 'we.water', 'action' => '*'],
            ['id' => 22, 'admin_role_id' => 2, 'controller' => 'we.electricity', 'action' => '*'],
            ['id' => 23, 'admin_role_id' => 2, 'controller' => 'bill', 'action' => 'index'],
            ['id' => 24, 'admin_role_id' => 2, 'controller' => 'bill.annual', 'action' => '*'],
            ['id' => 25, 'admin_role_id' => 2, 'controller' => 'bill.report', 'action' => '*'],
            ['id' => 26, 'admin_role_id' => 2, 'controller' => 'property', 'action' => '*'],
            ['id' => 27, 'admin_role_id' => 2, 'controller' => 'collected', 'action' => '*'],
            ['id' => 28, 'admin_role_id' => 2, 'controller' => 'uncollected', 'action' => '*'],
            ['id' => 29, 'admin_role_id' => 2, 'controller' => 'user', 'action' => '*'],
            ['id' => 30, 'admin_role_id' => 2, 'controller' => 'report', 'action' => '*'],
            ['id' => 31, 'admin_role_id' => 2, 'controller' => 'number', 'action' => '*'],
        ])->save();
    }
}
