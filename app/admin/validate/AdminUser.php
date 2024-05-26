<?php
namespace app\admin\validate;

use think\Validate;
use app\admin\model\AdminRole as RoleModel;
use app\admin\model\AdminUser as UserModel;

class AdminUser extends Validate
{
    protected $rule = [];

    protected $message = [
        'username.unique' => '用户名已存在',
    ];

    public function sceneInsert()
    {
        return $this->append('admin_role_id', 'checkAdminRoleId')
            ->append('username', 'unique:admin_user,username');
    }

    public function sceneUpdate()
    {
        return $this->append('admin_role_id', 'checkAdminRoleId')
            ->append('username', 'unique:admin_user,username');
    }

    public function checkAdminRoleId($value, $rule)
    {
        if (!RoleModel::field('id')->find($value)) {
            return '角色不存在';
        }
        return true;
    }
}
