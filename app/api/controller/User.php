<?php

namespace app\api\controller;

use app\api\validate\User as UserValidate;
use app\admin\model\AdminUser as UserModel;
use think\facade\Request;
use GuzzleHttp\Client;

class User extends Common
{
    protected $checkLoginExclude = ['login', 'loginWechat', 'register', 'renewal', 'getOpenid'];

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

    public function getOpenid()
    {
        $appId = env('APP_ID');
        $appSecret = env('APP_SECRET');
        $code = '微信小程序返回的code';
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appId}&secret={$appSecret}&js_code={$code}&grant_type=authorization_code";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $res = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($res, true);

        if (isset($data['openid'])) {
            $openid = $data['openid'];
            echo "OpenID: " . $openid;
        } else {
            echo "获取OpenID失败";
        }
    }
}
