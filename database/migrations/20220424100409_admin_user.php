<?php

use think\migration\Migrator;
use think\migration\db\Column;

class AdminUser extends Migrator
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
            'admin_user',
            ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']
        );
        $table->addColumn(
            'admin_role_id',
            'integer',
            ['null' => false, 'default' => 0, 'comment' => '角色id']
        )
        ->addColumn(
            'username',
            'string',
            ['limit' => 32, 'null' => false, 'default' => '', 'comment' => '用户名']
        )
        ->addColumn(
            'password',
            'string',
            ['limit' => 32, 'null' => false, 'default' => '', 'comment' => '密码']
        )
        ->addColumn(
            'salt',
            'char',
            ['limit' => 32, 'null' => false, 'default' => '', 'comment' => '密码salt']
        )
        ->addColumn(
            'openid',
            'string',
            ['limit' => 32, 'null' => true, 'default' => '', 'comment' => '微信小程序openID']
        )
        ->addColumn('expiration_date', 'timestamp', ['null' => true, 'comment' => '失效期'])
        ->addColumn('login_date', 'timestamp', ['null' => true, 'comment' => '最近一次登录日期'])
        ->addIndex(['username'], ['unique' => true])
        ->addTimestamps()
        ->create();
    }
}
