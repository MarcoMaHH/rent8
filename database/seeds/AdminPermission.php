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
            ['id' => 10, 'admin_role_id' => 2, 'controller' => 'index', 'action' => '*'],
            ['id' => 11, 'admin_role_id' => 2, 'controller' => 'house', 'action' => 'index'],
            ['id' => 12, 'admin_role_id' => 2, 'controller' => 'house.property', 'action' => '*'],
            ['id' => 13, 'admin_role_id' => 2, 'controller' => 'house.number', 'action' => '*'],
            ['id' => 14, 'admin_role_id' => 2, 'controller' => 'house.uncollected', 'action' => '*'],
            ['id' => 15, 'admin_role_id' => 2, 'controller' => 'house.collected', 'action' => '*'],
            ['id' => 16, 'admin_role_id' => 2, 'controller' => 'house.other', 'action' => '*'],
            ['id' => 17, 'admin_role_id' => 2, 'controller' => 'house.fee', 'action' => '*'],
            ['id' => 18, 'admin_role_id' => 2, 'controller' => 'house.receipt', 'action' => '*'],

            ['id' => 20, 'admin_role_id' => 2, 'controller' => 'tenant', 'action' => '*'],
            ['id' => 21, 'admin_role_id' => 2, 'controller' => 'tenant.message', 'action' => '*'],
            ['id' => 22, 'admin_role_id' => 2, 'controller' => 'tenant.contract', 'action' => '*'],
            ['id' => 23, 'admin_role_id' => 2, 'controller' => 'tenant.maintain', 'action' => '*'],

            ['id' => 30, 'admin_role_id' => 2, 'controller' => 'we', 'action' => 'index'],
            ['id' => 31, 'admin_role_id' => 2, 'controller' => 'we.meter', 'action' => '*'],
            ['id' => 32, 'admin_role_id' => 2, 'controller' => 'we.water', 'action' => '*'],
            ['id' => 33, 'admin_role_id' => 2, 'controller' => 'we.electricity', 'action' => '*'],

            ['id' => 40, 'admin_role_id' => 2, 'controller' => 'bill', 'action' => 'index'],
            ['id' => 41, 'admin_role_id' => 2, 'controller' => 'bill.report', 'action' => '*'],
            ['id' => 42, 'admin_role_id' => 2, 'controller' => 'bill.annual', 'action' => '*'],

            ['id' => 50, 'admin_role_id' => 2, 'controller' => 'admin.parameter', 'action' => '*'],

            ['id' => 60, 'admin_role_id' => 2, 'controller' => 'property', 'action' => '*'],
            ['id' => 61, 'admin_role_id' => 2, 'controller' => 'number', 'action' => '*'],
            ['id' => 62, 'admin_role_id' => 2, 'controller' => 'uncollected', 'action' => '*'],
            ['id' => 63, 'admin_role_id' => 2, 'controller' => 'collected', 'action' => '*'],
            ['id' => 64, 'admin_role_id' => 2, 'controller' => 'contract', 'action' => '*'],
            ['id' => 65, 'admin_role_id' => 2, 'controller' => 'report', 'action' => '*'],
            ['id' => 66, 'admin_role_id' => 2, 'controller' => 'user', 'action' => '*'],
            ['id' => 67, 'admin_role_id' => 2, 'controller' => 'fee', 'action' => '*'],
            ['id' => 68, 'admin_role_id' => 2, 'controller' => 'maintain', 'action' => '*'],
        ])->save();
    }
}
