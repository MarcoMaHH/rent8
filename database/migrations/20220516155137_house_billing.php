<?php

use think\migration\Migrator;
use think\migration\db\Column;

class HouseBilling extends Migrator
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
            'house_billing',
            ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']
        );
        $table->addColumn(
            'house_property_id',
            'integer',
            ['null' => false, 'default' => 0,  'comment' => '房产id']
        )
        ->addColumn(
            'house_number_id',
            'integer',
            ['null' => false, 'default' => 0,  'comment' => '房号id']
        )
        ->addColumn('start_time', 'timestamp', ['null' => true, 'comment' => '开始时间'])
        ->addColumn('end_time', 'timestamp', ['null' => true, 'comment' => '结束时间'])
        ->addColumn(
            'tenant_id',
            'integer',
            ['null' => false, 'default' => 0,  'comment' => '租客id']
        )
        ->addColumn('meter_reading_time', 'timestamp', ['null' => true, 'comment' => '抄表时间'])
        ->addColumn(
            'electricity_meter_this_month',
            'integer',
            ['limit' => 7, 'null' => true, 'comment' => '本月电表度数']
        )
        ->addColumn(
            'electricity_meter_last_month',
            'integer',
            ['limit' => 7, 'null' => true, 'comment' => '上月电表度数']
        )
        ->addColumn(
            'electricity_consumption',
            'integer',
            ['limit' => 7, 'null' => true, 'comment' => '电用量']
        )
        ->addColumn(
            'electricity',
            'float',
            ['null' => false, 'default' => 0.0, 'comment' => '电费']
        )
        ->addColumn(
            'water_meter_this_month',
            'integer',
            ['limit' => 7, 'null' => true, 'comment' => '本月水表度数']
        )
        ->addColumn(
            'water_meter_last_month',
            'integer',
            ['limit' => 7, 'null' => true, 'comment' => '上月水表度数']
        )
        ->addColumn(
            'water_consumption',
            'integer',
            ['limit' => 7, 'null' => true, 'comment' => '水用量']
        )
        ->addColumn(
            'water',
            'float',
            ['null' => false, 'default' => 0.0, 'comment' => '水费']
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
            'garbage_fee',
            'integer',
            ['null' => false, 'default' => 0, 'comment' => '卫生费']
        )
        ->addColumn(
            'management',
            'integer',
            ['null' => false, 'default' => 0, 'comment' => '管理费']
        )
        ->addColumn(
            'other_charges',
            'float',
            ['null' => false, 'default' => 0, 'comment' => '其他费用']
        )
        ->addColumn(
            'total_money',
            'double',
            ['null' => false, 'default' => 0.0, 'comment' => '总金额']
        )
        ->addColumn(
            'note',
            'string',
            ['limit' => 255, 'null' => true, 'default' => '', 'comment' => '备注']
        )
        ->addColumn('accounting_date', 'timestamp', ['null' => true, 'comment' => '到账时间'])
        ->addTimestamps()
        ->create();
    }
}
