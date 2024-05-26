<?php

use think\migration\Migrator;
use think\migration\db\Column;

class BillHydroelectricity extends Migrator
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
            'bill_hydroelectricity',
            ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']
        );
        $table->addColumn(
            'bill_meter_id',
            'integer',
            ['null' => false, 'default' => 0,  'comment' => '总表id']
        )
        ->addColumn(
            'type',
            'string',
            ['limit' => 2, 'null' => false, 'comment' => '类型：B电费,C水费']
        )
        ->addColumn(
            'amount',
            'float',
            ['null' => false, 'default' => 0.0, 'comment' => '金额']
        )
        ->addColumn(
            'dosage',
            'float',
            ['null' => false, 'default' => 0.0, 'comment' => '用量']
        )
        ->addColumn('calculate_date', 'timestamp', ['null' => true, 'comment' => '计算日期'])
        ->addTimestamps()
        ->create();
    }
}
