<?php

namespace app\api\controller;

use app\api\validate\User as UserValidate;
use app\admin\model\AdminUser as UserModel;
use think\facade\Request;

class User extends Common
{
    protected $checkLoginExclude = ['login'];

    public function login()
    {
        $data = [
            'name' => \json_decode($this->request->post('name/s', '', 'trim')),
            'password' => \json_decode($this->request->post('password/s', ''))
        ];
        $validate = new UserValidate();
        if (!$validate->check($data)) {
            return $this->returnError($validate->getError());
        }
        $result = $this->auth->login($data['name'], $data['password']);
        if (!$result) {
            return $this->returnError($this->auth->getError());
        }
        $loginUser = $this->auth->getLoginUser();
        $user = UserModel::find($loginUser['id']);
        $user->save(['login_date' => date("Y-m-d H:i:s")]);
        return $this->returnWechat($result, 0, '登陆成功。');
    }

    public function userinfo()
    {
        $user = $this->auth->getLoginUser();
        return $this->returnWechat([
            'id' => $user->id,
            'name' => $user->username,
            'role' => $user->admin_role_id
        ], 0, '');
    }

    public function logout()
    {
        $this->auth->logout();
        return $this->returnSuccess('退出成功');
    }
}
