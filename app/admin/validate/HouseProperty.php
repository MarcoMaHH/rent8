<?php
namespace app\admin\validate;

use think\Validate;
use app\admin\model\HouseNumber as NumberModel;

class HouseProperty extends Validate
{
    protected $rule = [];

    public function sceneDelete()
    {
        return $this->only(['id'])->append('id', 'checkNumberIsEmpty');
    }

    public function checkNumberIsEmpty($value, $rule)
    {
        if (NumberModel::field('id')->where('house_property_id', $value)->find()) {
            return '该房产已被用户使用';
        }
        return true;
    }
}
