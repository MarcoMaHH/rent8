<?php

use think\migration\Migrator;
use think\migration\db\Column;

class AdminParameter extends Migrator
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
            'admin_parameter',
            ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']
        );
        $table->addColumn('name', 'string', ['limit' => 32, 'null' => false, 'comment' => '名称'])
            ->addColumn('label', 'string', ['limit' => 16, 'null' => false, 'comment' => '标签'])
            ->addColumn('value', 'string', ['limit' => 16, 'null' => false, 'comment' => '值'])
            ->addColumn('note', 'string', ['limit' => 32, 'null' => true, 'comment' => '备注'])
            ->addTimestamps()
            ->create();
    }
}
