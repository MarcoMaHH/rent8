<?php

use think\migration\Migrator;
use think\migration\db\Column;

class HouseOther extends Migrator
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
            'house_other',
            ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']
        );
        $table->addColumn(
            'house_property_id',
            'integer',
            ['null' => false, 'default' => 0,  'comment' => '房产id']
        )
        ->addColumn(
            'type',
            'string',
            ['limit' => 4, 'null' => false, 'default' => '', 'comment' => '类型']
        )
        ->addColumn(
            'total_money',
            'float',
            ['null' => false, 'default' => 0.0, 'comment' => '金额']
        )
        ->addColumn(
            'note',
            'string',
            ['limit' => 225, 'null' => true, 'default' => '', 'comment' => '备注']
        )
        ->addColumn(
            'accout_mark',
            'string',
            ['limit' => 4, 'null' => false, 'default' => 'N', 'comment' => '是否到账']
        )
        ->addColumn(
            'circulate_mark',
            'string',
            ['limit' => 4, 'null' => false, 'default' => 'N', 'comment' => '是否重复']
        )
        ->addColumn('accounting_date', 'timestamp', ['null' => true, 'comment' => '到账时间'])
        ->addTimestamps()
        ->create();
    }
}
