<?php
namespace app\admin\validate;

use app\admin\model\AdminMenu as MenuModel;
use think\Validate;

class AdminMenu extends Validate
{
    protected $rule = [];

    protected $message = [
        'pid.different' => '不能选择自己作为上级菜单',
    ];

    public function sceneInsert()
    {
        return $this->append('pid', 'checkPidIsTop');
    }

    public function sceneUpdate()
    {
        return $this->append('pid', 'checkPidIsTop')->append('pid', 'different:id');
    }

    public function checkPidIsTop($value, $rule)
    {
        if ($value !== 0) {
            if (!$data = MenuModel::field('pid')->find($value)) {
                return '上级菜单不存在';
            }
            if ($data->pid) {
                return '上级菜单不能使用子项';
            }
        }
        return true;
    }

    public function sceneDelete()
    {
        return $this->only(['id'])->append('id', 'checkIdIsLeaf');
    }

    public function checkIdIsLeaf($value, $rule)
    {
        $data = MenuModel::field('id')->where('pid', $value)->find();
        return $data ? '存在子项' : true;
    }
}
