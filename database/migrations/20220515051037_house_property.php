<?php

use think\migration\Migrator;
use think\migration\db\Column;

class HouseProperty extends Migrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $table = $this->table(
            'house_property',
            ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']
        );
        $table->addColumn(
            'admin_user_id',
            'integer',
            ['null' => false, 'default' => 0,  'comment' => '用户id']
        )
        ->addColumn(
            'name',
            'string',
            ['limit' => 32, 'null' => false, 'default' => '', 'comment' => '房产名']
        )
        ->addColumn(
            'address',
            'string',
            ['limit' => 255, 'comment' => '详细地址']
        )
        ->addColumn(
            'landlord',
            'string',
            ['limit' => 32, 'comment' => '房东名称']
        )
        ->addColumn(
            'phone',
            'string',
            ['limit' => 32, 'comment' => '房东手机']
        )
        ->addColumn(
            'id_card',
            'string',
            ['limit' => 32, 'comment' => '房东身份证']
        )
        ->addColumn(
            'firstly',
            'string',
            ['null' => false, 'default' => 'N', 'comment' => '是否默认']
        )
        ->addTimestamps()
        ->create();
    }
}
