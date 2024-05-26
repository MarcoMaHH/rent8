<?php
namespace app\admin\model;

use think\Model;

class AdminPermission extends Model
{
    public function setActionAttr($value)
    {
        return \implode(',', \array_map('trim', \explode(',', $value)));
    }
}
