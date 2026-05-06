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
                'tenant_message_id',
                'integer',
                ['null' => true,  'comment' => '租客id']
            )
            ->addColumn(
                'house_receipt_id',
                'integer',
                ['null' => true,  'comment' => '当前收据单号']
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
                'daily_rent',
                'integer',
                ['null' => false, 'default' => 0, 'comment' => '逾期日租金']
            )
            ->addColumn(
                'checkin_date',
                'timestamp',
                ['null' => true, 'comment' => '入住日期']
            )
            ->addColumn(
                'payment_date',
                'timestamp',
                ['null' => true, 'comment' => '收款日期']
            )
            ->addColumn(
                'cycle',
                'integer',
                ['null' => false, 'default' => 1, 'comment' => '周期']
            )
            ->addColumn(
                'cycle_type',
                'string',
                ['limit' => 10, 'null' => false, 'default' => 'month', 'comment' => '周期类型']
            )
            ->addColumn(
                'calculate_date',
                'timestamp',
                ['null' => false, 'comment' => '计算日期']
            )
            ->addColumn(
                'lease',
                'integer',
                ['null' => false, 'default' => 0, 'comment' => '计算期数']
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
                'fees1',
                'integer',
                ['null' => false, 'default' => 0, 'comment' => '费用1']
            )
            ->addColumn(
                'fees2',
                'integer',
                ['null' => false, 'default' => 0, 'comment' => '费用2']
            )
            ->addColumn(
                'fees3',
                'integer',
                ['null' => false, 'default' => 0, 'comment' => '费用3']
            )
            ->addColumn(
                'fees4',
                'float',
                ['null' => false, 'default' => 0, 'comment' => '费用4']
            )
            ->addColumn(
                'equipment',
                'string',
                ['limit' => 255, 'null' => true, 'comment' => '房间设备']
            )
            ->addColumn('delete_time', 'timestamp', ['null' => true, 'comment' => '删除时间'])
            ->addTimestamps()
            ->create();
    }
}
