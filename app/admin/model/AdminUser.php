<?php
namespace app\admin\model;

use think\Model;

class AdminUser extends Model
{
    public function adminRole()
    {
        return $this->belongsTo('AdminRole');
    }

    public function setPasswordAttr($value, $data)
    {
        $salt = md5(uniqid(microtime(), true));
        $data['salt'] =  $salt;
        $this->data($data);
        return md5(md5($value) . $salt);
    }

    public function adminPermission()
    {
        return $this->hasMany('AdminPermission', 'admin_role_id', 'admin_role_id');
    }

    public function houseNumber()
    {
        return $this->hasManyThrough('HouseNumber', 'HouseProperty');
    }

    public function houseBilling()
    {
        return $this->hasManyThrough('HouseBilling', 'HouseProperty');
    }
}
