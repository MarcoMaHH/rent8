<?php

namespace app\api\controller;

use app\api\validate\User as UserValidate;
use app\admin\model\AdminUser as UserModel;
use think\facade\Request;

class User extends Common
{
    protected $checkLoginExclude = ['login', 'loginWechat', 'register', 'renewal'];

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

    public function bindWechat()
    {
        $result = $this->auth->bindWechat(Request::header('x-wx-openid'));
        if (!$result) {
            return $this->returnError($this->auth->getError());
        }
        return $this->returnSuccess('绑定成功');
    }

    public function loginWechat()
    {
        $openid = Request::header('x-wx-openid');
        $result = $this->auth->loginWechat($openid);
        if (!$result) {
            return $this->returnError($this->auth->getError());
        }
        $loginUser = $this->auth->getLoginUser();
        $user = UserModel::find($loginUser['id']);
        $user->save(['login_date' => date("Y-m-d H:i:s")]);
        return $this->returnWechat($result, 0, '登陆成功。');
    }

    public function register()
    {
        $openid = Request::header('x-wx-openid');
        $data = [
            'username' => $this->request->post('name/s', '', 'trim'),
            'admin_role_id' => 4,
            'expiration_date' => date("Y-m-d H:i:s", strtotime(" +30 day")),
            'password' => $this->request->post('password/s', '', 'trim'),
            'openid' => $openid,
        ];
        if ($user = UserModel::where('username', $data['username'])->select()->toArray()) {
            return $this->returnError('用户名已存在');
        }
        UserModel::create($data);
        return $this->returnSuccess('注册成功');
    }

    public function renewal()
    {
        $openid = Request::header('x-wx-openid');
        if ($user = UserModel::where('openid', $openid)->find()) {
            $user->save(['expiration_date' => date("Y-m-d H:i:s", strtotime("+15 day", strtotime($user->expiration_date)))]);
            return $this->returnSuccess('续期成功');
        }
        return $this->returnError('用户未绑定微信');
    }

    public function logout()
    {
        $this->auth->logout();
        return $this->returnSuccess('退出成功');
    }
}
