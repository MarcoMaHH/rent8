<?php

use think\migration\Migrator;
use think\migration\db\Column;

class TenantMaintain extends Migrator
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
            'tenant_maintain',
            ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']
        );
        $table
            ->addColumn(
                'house_property_id',
                'integer',
                ['null' => false, 'default' => 0,  'comment' => '房产id']
            )
            ->addColumn(
                'house_number_id',
                'integer',
                ['null' => false, 'default' => 0,  'comment' => '房间id']
            )
            ->addColumn(
                'status',
                'string',
                ['limit' => 4, 'null' => false, 'default' => 'N', 'comment' => '状态(Y:已解决,N:未解决)']
            )
            ->addColumn(
                'description',
                'string',
                ['limit' => 255, 'null' => true, 'default' => '', 'comment' => '说明']
            )
            ->addTimestamps()
            ->create();
    }
}
