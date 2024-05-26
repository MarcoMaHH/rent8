<?php
namespace app\admin\validate;

use think\Validate;
use app\admin\model\HouseNumber as NumberModel;

class HouseTenant extends Validate
{
    protected $rule = [];

    public function sceneDelete()
    {
        return $this->only(['id'])->append('id', 'checkNumberIsEmpty');
    }

    public function checkNumberIsEmpty($value, $rule)
    {
        if (NumberModel::field('id')->where('tenant_id', $value)->find()) {
            return '该租客还在呢';
        }
        return true;
    }
}
