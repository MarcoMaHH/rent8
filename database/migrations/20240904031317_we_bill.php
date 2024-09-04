<?php

use think\migration\Migrator;
use think\migration\db\Column;

class WeBill extends Migrator
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
            'we_bill',
            ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']
        );
        $table->addColumn(
            'bill_meter_id',
            'integer',
            ['null' => false, 'default' => 0,  'comment' => '总表id']
        )
        ->addColumn(
            'house_property_id',
            'integer',
            ['limit' => 6, 'null' => false, 'default' => 0,  'comment' => '房产id']
        )
        ->addColumn('accounting_date', 'string', ['limit' => 10, 'null' => true, 'comment' => '到账日期'])
        ->addColumn('start_month', 'timestamp', ['null' => true, 'comment' => '开始月份'])
        ->addColumn('end_month', 'timestamp', ['null' => true, 'comment' => '结束月份'])
        ->addColumn(
            'master_dosage',
            'integer',
            ['null' => false, 'default' => 0,  'comment' => '总表用量']
        )
        ->addColumn(
            'master_sum',
            'float',
            ['null' => false, 'default' => 0,  'comment' => '总表金额']
        )
        ->addTimestamps()
        ->create();
    }
}
