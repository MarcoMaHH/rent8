<?php

use think\migration\Migrator;
use think\migration\db\Column;

class HouseNumber extends Migrator
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
            'house_number',
            ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']
        );
        $table->addColumn(
            'house_property_id',
            'integer',
            ['null' => false, 'default' => 0,  'comment' => '房产id']
        )
        ->addColumn(
            'name',
            'string',
            ['limit' => 32, 'null' => false, 'default' => '', 'comment' => '房号名']
        )
        ->addColumn(
            'rental',
            'integer',
            ['null' => false, 'default' => 0, 'comment' => '租金']
        )
        ->addColumn(
            'deposit',
            'integer',
            ['null' => false, 'default' => 0, 'comment' => '押金']
        )
        ->addColumn(
            'management',
            'integer',
            ['null' => false, 'default' => 0, 'comment' => '管理费']
        )
        ->addColumn(
            'garbage_fee',
            'integer',
            ['null' => false, 'default' => 0, 'comment' => '垃圾费']
        )
        ->addColumn(
            'daily_rent',
            'integer',
            ['null' => false, 'default' => 0, 'comment' => '逾期日租金']
        )
        ->addColumn(
            'water_price',
            'float',
            ['null' => false, 'default' => 0.0, 'comment' => '水费单价']
        )
        ->addColumn(
            'electricity_price',
            'float',
            ['null' => false, 'default' => 0.0, 'comment' => '电费单价']
        )
        ->addColumn(
            'rent_mark',
            'string',
            ['limit' => 4, 'null' => false, 'default' => 'N', 'comment' => '是否在租']
        )
        ->addColumn('payment_time', 'timestamp', ['null' => true, 'comment' => '收款时间'])
        ->addColumn(
            'receipt_number',
            'integer',
            ['null' => true, 'comment' => '当前收据单号']
        )
        ->addColumn(
            'tenant_id',
            'integer',
            ['null' => true, 'comment' => '当前租客id']
        )
        ->addColumn('checkin_time', 'timestamp', ['null' => true, 'comment' => '入住时间'])
        ->addColumn(
            'lease',
            'integer',
            ['null' => false, 'default' => 0, 'comment' => '租期']
        )
        ->addColumn(
            'lease_type',
            'string',
            ['limit' => 2, 'null' => false, 'default' => '1', 'comment' => '租期类别']
        )
        ->addColumn('delete_time', 'timestamp', ['null' => true, 'comment' => '删除时间'])
        ->addTimestamps()
        ->create();
    }
}
