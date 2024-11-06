<?php

use think\migration\Migrator;
use think\migration\db\Column;

class HouseContract extends Migrator
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
            'house_contract',
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
        ->addColumn('start_date', 'timestamp', ['null' => true, 'comment' => '开始日期'])
        ->addColumn('end_date', 'timestamp', ['null' => true, 'comment' => '结束日期'])
        ->addTimestamps()
        ->create();
    }
}
