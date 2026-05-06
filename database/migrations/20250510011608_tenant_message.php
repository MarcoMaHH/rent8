<?php

use think\migration\Migrator;
use think\migration\db\Column;

class TenantMessage extends Migrator
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
            'tenant_message',
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
                ['null' => false, 'default' => 0,  'comment' => '房号id']
            )
            ->addColumn(
                'pid',
                'integer',
                ['null' => false, 'default' => 0, 'comment' => '上级id']
            )
            ->addColumn(
                'name',
                'string',
                ['limit' => 32, 'null' => false, 'default' => '', 'comment' => '姓名']
            )
            ->addColumn(
                'sex',
                'string',
                ['limit' => 4, 'null' => false, 'default' => '', 'comment' => '性别']
            )
            ->addColumn(
                'checkout_mark',
                'string',
                ['limit' => 4, 'null' => false, 'default' => 'N', 'comment' => '退房标注']
            )
            ->addColumn(
                'phone',
                'string',
                ['limit' => 11, 'null' => true, 'default' => '', 'comment' => '电话号码']
            )
            ->addColumn(
                'id_card_number',
                'string',
                ['limit' => 18, 'null' => true, 'default' => '', 'comment' => '身份证号码']
            )
            ->addColumn(
                'native_place',
                'string',
                ['limit' => 32, 'null' => true, 'default' => '', 'comment' => '籍贯']
            )
            ->addColumn(
                'work_units',
                'string',
                ['limit' => 225, 'null' => true, 'default' => '', 'comment' => '工作单位']
            )
            ->addColumn(
                'note',
                'string',
                ['limit' => 255, 'null' => true, 'default' => '', 'comment' => '备注']
            )
            ->addColumn('checkin_date', 'timestamp', ['null' => true, 'comment' => '入住日期'])
            ->addColumn('leave_date', 'timestamp', ['null' => true, 'comment' => '离开日期'])
            ->addTimestamps()
            ->create();
    }
}
