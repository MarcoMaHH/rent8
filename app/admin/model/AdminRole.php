<?php

namespace app\admin\model;

use think\Model;

class AdminRole extends Model
{
    public function adminPermission()
    {
        return $this->hasMany('AdminPermission');
    }
}
