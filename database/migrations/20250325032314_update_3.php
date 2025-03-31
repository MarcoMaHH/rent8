
<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Update3 extends Migrator
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
        $table = $this->table('house_property');
        $table->addColumn(
            'subscribe_mark',
            'string',
            ['limit' => 4, 'null' => false, 'default' => 'Y', 'comment' => '是否订阅消息']
        )->update();

        $table2 = $this->table(
            'pay_photo',
            ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']
        );
        $table2->addColumn(
            'house_property_id',
            'integer',
            ['null' => false, 'default' => 0,  'comment' => '房产id']
        )
            ->addColumn(
                'url',
                'string',
                ['limit' => 64, 'null' => false, 'default' => '', 'comment' => '照片路径']
            )
            ->addTimestamps()
            ->create();
    }
}
