<?php

use think\migration\Migrator;
use think\migration\db\Column;

class AdminSubscribe extends Migrator
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
            'admin_subscribe',
            ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']
        );
        $table->addColumn('open_id', 'string', ['limit' => 32, 'null' => false, 'comment' => '微信小程序openID'])
            ->addColumn('thing', 'string', ['limit' => 32, 'null' => false, 'comment' => '名称'])
            ->addColumn('time', 'timestamp', ['null' => false, 'comment' => '日期'])
            ->addColumn('amount', 'integer', ['null' => false, 'default' => 0, 'comment' => '金额'])
            ->addColumn(
                'subscribe_type',
                'string',
                ['limit' => 4, 'null' => false, 'default' => 'rent', 'comment' => '订阅消息类型']
            )
            ->addTimestamps()
            ->create();
    }
}
