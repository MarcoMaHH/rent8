<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Update1 extends Migrator
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
        // 获取 rent_house_number 表对象
        $table = $this->table('house_number');

        // 添加 device 字段，类型为 string，长度限制为 255，允许为空
        $table->addColumn('equipment', 'string', ['limit' => 255, 'null' => true, 'comment' => '房间设备'])
            ->update(); // 不要忘记调用 update 方法来应用更改

        $table2 = $this->table(
            'contract_photo',
            ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']
        );
        $table2->addColumn(
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
                'contract_id',
                'integer',
                ['null' => false, 'default' => 0,  'comment' => '合同id']
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
