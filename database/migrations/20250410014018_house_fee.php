<?php

use think\migration\Migrator;
use think\migration\db\Column;

class HouseFee extends Migrator
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
            'house_fee',
            ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']
        );
        $table
            ->addColumn(
                'admin_user_id',
                'integer',
                ['null' => false, 'default' => 0,  'comment' => '用户id']
            )
            ->addColumn(
                'column_id',
                'string',
                ['limit' => 20, 'null' => false, 'comment' => '栏位id']
            )
            ->addColumn(
                'column_name',
                'string',
                ['limit' => 20, 'null' => true, 'comment' => '栏位名称']
            )
            ->addTimestamps()
            ->create();
    }
}
