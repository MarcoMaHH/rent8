<?php

use think\migration\Seeder;

class AdminParameter extends Seeder
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run(): void
    {
        $this->table('admin_parameter')->insert([
            ['id' => 1, 'name' => 'ymd', 'label' => '年', 'value' => 'year'],
            ['id' => 2, 'name' => 'ymd', 'label' => '月', 'value' => 'month'],
            ['id' => 3, 'name' => 'ymd', 'label' => '日', 'value' => 'day'],
            ['id' => 11, 'name' => 'bill_type', 'label' => '房租账单', 'value' => 'A', 'note' => 'primary'],
            ['id' => 12, 'name' => 'bill_type', 'label' => '逾期账单', 'value' => 'B', 'note' => 'danger'],
            ['id' => 13, 'name' => 'bill_type', 'label' => '退房账单', 'value' => 'C', 'note' => 'default'],
        ])->save();
    }
}
