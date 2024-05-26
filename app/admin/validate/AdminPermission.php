<?php
namespace app\admin\validate;

use app\admin\model\AdminRole as RoleModel;
use think\Validate;

class AdminPermission extends Validate
{
    public function sceneInsert()
    {
        return $this->append('admin_role_id', 'checkAdminRoleId');
    }

    public function sceneUpdate()
    {
        return $this->append('admin_role_id', 'checkAdminRoleId');
    }

    public function checkAdminRoleId($value)
    {
        if (!RoleModel::field('id')->find($value)) {
            return '角色不存在';
        }
        return true;
    }
}
