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
            ['id' => 9, 'admin_role_id' => 2, 'controller' => 'bill', 'action' => 'index'],
            ['id' => 10, 'admin_role_id' => 2, 'controller' => 'bill.electricity', 'action' => 'index,queryElectricity,queryMeter'],
            ['id' => 11, 'admin_role_id' => 2, 'controller' => 'bill.water', 'action' => 'index,query,queryMeter'],
            ['id' => 12, 'admin_role_id' => 2, 'controller' => 'bill.other', 'action' => 'index,query'],
            ['id' => 13, 'admin_role_id' => 2, 'controller' => 'bill.report', 'action' => 'index,query,echar'],
            ['id' => 14, 'admin_role_id' => 2, 'controller' => 'property', 'action' => 'query,sort,edit'],
            ['id' => 15, 'admin_role_id' => 2, 'controller' => 'number', 'action' => 'query,edit,rental'],
            ['id' => 16, 'admin_role_id' => 2, 'controller' => 'uncollected', 'action' => 'query,queryReadingTime,report,edit'],
            ['id' => 17, 'admin_role_id' => 2, 'controller' => 'collected', 'action' => '*'],
            ['id' => 18, 'admin_role_id' => 2, 'controller' => 'user', 'action' => 'login,userinfo,loginWechat,register,renewal,logout'],
            ['id' => 19, 'admin_role_id' => 2, 'controller' => 'report', 'action' => '*'],
            ['id' => 20, 'admin_role_id' => 2, 'controller' => 'index', 'action' => '*'],
            // VIP会员
            ['id' => 21, 'admin_role_id' => 3, 'controller' => 'index', 'action' => '*'],
            ['id' => 22, 'admin_role_id' => 3, 'controller' => 'house', 'action' => 'index'],
            ['id' => 23, 'admin_role_id' => 3, 'controller' => 'house.property', 'action' => '*'],
            ['id' => 24, 'admin_role_id' => 3, 'controller' => 'house.number', 'action' => '*'],
            ['id' => 25, 'admin_role_id' => 3, 'controller' => 'house.uncollected', 'action' => '*'],
            ['id' => 26, 'admin_role_id' => 3, 'controller' => 'house.collected', 'action' => '*'],
            ['id' => 27, 'admin_role_id' => 3, 'controller' => 'house.tenant', 'action' => '*'],
            ['id' => 28, 'admin_role_id' => 3, 'controller' => 'bill', 'action' => 'index'],
            ['id' => 29, 'admin_role_id' => 3, 'controller' => 'bill.electricity', 'action' => '*'],
            ['id' => 30, 'admin_role_id' => 3, 'controller' => 'bill.water', 'action' => '*'],
            ['id' => 31, 'admin_role_id' => 3, 'controller' => 'bill.other', 'action' => '*'],
            ['id' => 32, 'admin_role_id' => 3, 'controller' => 'bill.report', 'action' => '*'],
            ['id' => 33, 'admin_role_id' => 3, 'controller' => 'property', 'action' => '*'],
            ['id' => 34, 'admin_role_id' => 3, 'controller' => 'number', 'action' => '*'],
            ['id' => 35, 'admin_role_id' => 3, 'controller' => 'uncollected', 'action' => '*'],
            ['id' => 36, 'admin_role_id' => 3, 'controller' => 'collected', 'action' => '*'],
            ['id' => 37, 'admin_role_id' => 3, 'controller' => 'report', 'action' => '*'],
            ['id' => 38, 'admin_role_id' => 3, 'controller' => 'user', 'action' => '*'],
            ['id' => 39, 'admin_role_id' => 3, 'controller' => 'index', 'action' => '*'],
            // 小程序
            ['id' => 40, 'admin_role_id' => 4, 'controller' => 'property', 'action' => '*'],
            ['id' => 41, 'admin_role_id' => 4, 'controller' => 'number', 'action' => '*'],
            ['id' => 42, 'admin_role_id' => 4, 'controller' => 'uncollected', 'action' => '*'],
            ['id' => 43, 'admin_role_id' => 4, 'controller' => 'collected', 'action' => '*'],
            ['id' => 44, 'admin_role_id' => 4, 'controller' => 'report', 'action' => '*'],
            ['id' => 45, 'admin_role_id' => 4, 'controller' => 'user', 'action' => '*'],
            ['id' => 46, 'admin_role_id' => 4, 'controller' => 'index', 'action' => '*'],
        ])->save();
    }
}
