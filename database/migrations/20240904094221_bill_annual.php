<?php

use think\migration\Migrator;
use think\migration\db\Column;

class BillAnnual extends Migrator
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
            'bill_annual',
            ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']
        );
        $table->addColumn(
            'admin_user_id',
            'integer',
            ['null' => false, 'default' => 0,  'comment' => '用户id']
        )->addColumn(
            'house_property_id',
            'integer',
            ['limit' => 6, 'null' => false, 'default' => 0,  'comment' => '房产id']
        )
        ->addColumn(
            'income',
            'float',
            ['null' => false, 'default' => 0.0, 'comment' => '收入']
        )
        ->addColumn(
            'expenditure',
            'float',
            ['null' => false, 'default' => 0.0, 'comment' => '支持']
        )
        ->addColumn('annual', 'string', ['limit' => 6, 'null' => false, 'comment' => '年度'])
        ->addTimestamps()
        ->create();
    }
}
