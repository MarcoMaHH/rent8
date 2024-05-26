<?php
namespace app\admin\validate;

use app\admin\model\AdminRole as RoleModel;
use app\admin\model\HouseProperty as PropertyModel;
use think\Validate;

class HouseNumber extends Validate
{
    protected $rule = [
    ];
    protected $message = [
    ];

    public function sceneInsert()
    {
        return $this->append('house_property_id', 'checkHousePropertyId');
    }

    public function sceneUpdate()
    {
        return $this->append('house_property_id', 'checkHousePropertyId');
    }

    public function checkHousePropertyId($value, $rule)
    {
        if (!PropertyModel::field('id')->find($value)) {
            return '房产不存在';
        }
        return true;
    }
}
