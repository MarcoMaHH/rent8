<?php

namespace app\api\controller;

use app\api\validate\User as UserValidate;
use app\admin\model\AdminUser as UserModel;
use think\facade\Request;
use GuzzleHttp\Client;

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

    public function loginWechat()
    {
        $appId = env('APP_ID');
        $appSecret = env('APP_SECRET');
        $code = \json_decode($this->request->post('code/s', '', 'trim'));
        $client = new Client();
        try {
            // 构造微信登录凭证校验接口URL
            $url = "https://api.weixin.qq.com/sns/jscode2session";
            $response = $client->request('GET', $url, [
                'verify' => false, // 禁用 SSL 证书验证
                'query' => [
                    'appid' => $appId,
                    'secret' => $appSecret,
                    'js_code' => $code,
                    'grant_type' => 'authorization_code'
                ]
            ]);
            $body = $response->getBody()->getContents();
            $result = json_decode($body, true);
            if (isset($result['openid'])) {
                $openid = $result['openid'];
                $result = $this->auth->loginWechat($openid);
                if (!$result) {
                    return $this->returnError($this->auth->getError());
                }
                return $this->returnWechat($result, 0, '登陆成功。');
            } else {
                return $this->returnError('微信登陆失败');
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }

    public function register()
    {
        $data = [
            'username' => $this->request->post('name/s', '', 'trim'),
            'admin_role_id' => 2,
            'expiration_date' => date("Y-m-d H:i:s", strtotime(" +30 day")),
            'password' => $this->request->post('password/s', '', 'trim'),
        ];
        if ($user = UserModel::where('username', $data['username'])->select()->toArray()) {
            return $this->returnError('用户名已存在');
        }

        $appId = env('APP_ID');
        $appSecret = env('APP_SECRET');
        $code = $this->request->post('code/s', '', 'trim');
        $client = new Client();
        try {
            // 构造微信登录凭证校验接口URL
            $url = "https://api.weixin.qq.com/sns/jscode2session";
            $response = $client->request('GET', $url, [
                'verify' => false, // 禁用 SSL 证书验证
                'query' => [
                    'appid' => $appId,
                    'secret' => $appSecret,
                    'js_code' => $code,
                    'grant_type' => 'authorization_code'
                ]
            ]);
            $body = $response->getBody()->getContents();
            $result = json_decode($body, true);
            if (isset($result['openid'])) {
                $data['openid'] = $result['openid'];
                UserModel::create($data);
                return $this->returnSuccess('注册成功');
            } else {
                return $this->returnError('注册失败');
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }

    public function renewal()
    {
        $appId = env('APP_ID');
        $appSecret = env('APP_SECRET');
        $code = $this->request->post('code/s', '', 'trim');
        $client = new Client();
        try {
            // 构造微信登录凭证校验接口URL
            $url = "https://api.weixin.qq.com/sns/jscode2session";
            $response = $client->request('GET', $url, [
                'verify' => false, // 禁用 SSL 证书验证
                'query' => [
                    'appid' => $appId,
                    'secret' => $appSecret,
                    'js_code' => $code,
                    'grant_type' => 'authorization_code'
                ]
            ]);
            $body = $response->getBody()->getContents();
            $result = json_decode($body, true);
            if (isset($result['openid'])) {
                $openid = $result['openid'];
                if ($user = UserModel::where('openid', $openid)->find()) {
                    $user->save(['expiration_date' => date("Y-m-d H:i:s", strtotime("+15 day", strtotime($user->expiration_date)))]);
                    return $this->returnSuccess('续期成功');
                }
                return $this->returnError('用户未绑定微信');
            } else {
                return $this->returnError('账号续期失败');
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }

    public function bindWechat()
    {
        $appId = env('APP_ID');
        $appSecret = env('APP_SECRET');
        $code = $this->request->post('code/s', '', 'trim');
        $client = new Client();
        try {
            // 构造微信登录凭证校验接口URL
            $url = "https://api.weixin.qq.com/sns/jscode2session";
            $response = $client->request('GET', $url, [
                'verify' => false, // 禁用 SSL 证书验证
                'query' => [
                    'appid' => $appId,
                    'secret' => $appSecret,
                    'js_code' => $code,
                    'grant_type' => 'authorization_code'
                ]
            ]);
            $body = $response->getBody()->getContents();
            $result = json_decode($body, true);
            if (isset($result['openid'])) {
                $openid = $result['openid'];
                $result = $this->auth->bindWechat($openid);
                if (!$result) {
                    return $this->returnError($this->auth->getError());
                }
                return $this->returnSuccess('绑定成功');
            } else {
                return $this->returnError('账号绑定失败');
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }

    public function logout()
    {
        $this->auth->logout();
        return $this->returnSuccess('退出成功');
    }
}
