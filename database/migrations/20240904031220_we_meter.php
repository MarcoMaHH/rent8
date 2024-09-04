<?php

use think\migration\Migrator;
use think\migration\db\Column;

class WeMeter extends Migrator
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
            'we_meter',
            ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']
        );
        $table->addColumn(
            'name',
            'string',
            ['limit' => 32, 'null' => false, 'default' => '', 'comment' => '名称']
        )
        ->addColumn(
            'type',
            'string',
            ['limit' => 4, 'null' => false, 'default' => '',  'comment' => '类型']
        )
        ->addColumn(
            'house_property_id',
            'integer',
            ['null' => false, 'default' => 0,  'comment' => '房产id']
        )
        ->addColumn(
            'house_number_id',
            'string',
            ['limit' => 128, 'null' => false, 'default' => '',  'comment' => '房号id']
        )
        ->addTimestamps()
        ->create();
    }
}
