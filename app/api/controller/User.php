<?php

namespace app\api\controller;

use app\api\validate\User as UserValidate;
use app\admin\model\AdminUser as UserModel;
use app\admin\model\HouseBilling as BillingModel;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\AdminSubscribe as SubscribeModel;

class User extends Common
{
    protected $checkLoginExclude = ['login', 'loginWechat', 'register', 'renewal'];

    public function login()
    {
        $data = [
            'name' => $this->request->post('name/s', '', 'trim'),
            'password' => $this->request->post('password/s', '')
        ];
        $validate = new UserValidate();
        if (!$validate->check($data)) {
            return $this->returnError($validate->getError());
        }
        $result = $this->auth->login($data['name'], $data['password']);
        if (!$result) {
            return $this->returnError($this->auth->getError());
        }
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
        $code = $this->request->post('code/s', '', 'trim');
        try {
            $ch = curl_init();
            // 构建包含查询字符串的URL
            $url = 'https://api.weixin.qq.com/sns/jscode2session?' . http_build_query([
                'appid' => env('WECHAT_APP_ID'),
                'secret' => env('WECHAT_APP_SECRET'),
                'js_code' => $code,
                'grant_type' => 'authorization_code'
            ]);

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);

            if ($response === false) {
                return $this->returnSuccess(curl_error($ch));
            }
            curl_close($ch);
            $result = json_decode($response, true);
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

    // 注册账号
    public function register()
    {
        $data = [
            'username' => $this->request->post('name/s', '', 'trim'),
            'admin_role_id' => 2,
            'expiration_date' => date("Y-m-d H:i:s", strtotime(" +30 day")),
            'password' => $this->request->post('password/s', '', 'trim'),
        ];
        if (UserModel::where('username', $data['username'])->select()->toArray()) {
            return $this->returnError('用户名已存在');
        }
        $code = $this->request->post('code/s', '', 'trim');

        try {
            $ch = curl_init();
            // 构建包含查询字符串的URL
            $url = 'https://api.weixin.qq.com/sns/jscode2session?' . http_build_query([
                'appid' => env('WECHAT_APP_ID'),
                'secret' => env('WECHAT_APP_SECRET'),
                'js_code' => $code,
                'grant_type' => 'authorization_code'
            ]);

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);

            if ($response === false) {
                return $this->returnSuccess(curl_error($ch));
            }
            curl_close($ch);
            $result = json_decode($response, true);
            if (isset($result['openid'])) {
                $data['openid'] = $result['openid'];
                UserModel::create($data);
                return $this->returnSuccess('注册成功');
            } else {
                // 确认APP_SECRET是否开启
                return $this->returnError('注册失败');
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }

    // 账号续期
    public function renewal()
    {
        $code = $this->request->post('code/s', '', 'trim');
        try {
            $ch = curl_init();
            // 构建包含查询字符串的URL
            $url = 'https://api.weixin.qq.com/sns/jscode2session?' . http_build_query([
                'appid' => env('WECHAT_APP_ID'),
                'secret' => env('WECHAT_APP_SECRET'),
                'js_code' => $code,
                'grant_type' => 'authorization_code'
            ]);

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);

            if ($response === false) {
                return $this->returnSuccess(curl_error($ch));
            }
            curl_close($ch);
            $result = json_decode($response, true);
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
        $code = $this->request->post('code/s', '', 'trim');
        try {
            $ch = curl_init();
            // 构建包含查询字符串的URL
            $url = 'https://api.weixin.qq.com/sns/jscode2session?' . http_build_query([
                'appid' => env('WECHAT_APP_ID'),
                'secret' => env('WECHAT_APP_SECRET'),
                'js_code' => $code,
                'grant_type' => 'authorization_code'
            ]);

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);

            if ($response === false) {
                return $this->returnSuccess(curl_error($ch));
            }
            curl_close($ch);
            $result = json_decode($response, true);
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

    // 微信小程序订阅消息
    public function subscribe()
    {
        $loginUser = $this->auth->getLoginUser();
        $properties = PropertyModel::where('admin_user_id', $loginUser['id'])
            ->select()
            ->toArray();
        $result = array_map(function ($property) {
            return $property['id'];
        }, $properties);
        $conditions = array(
            ['a.house_property_id', 'in', $result],
            ['a.start_time', '> time', 'today'],
            ['a.accounting_date', 'null', ''],
            ['c.subscribe_mark', '=', 'Y'],
        );
        $bill = BillingModel::where($conditions)
            ->alias('a')
            ->join('HouseNumber b', 'b.house_property_id = a.house_property_id and b.id = a.house_number_id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->field('a.id, a.total_money, a.house_property_id, a.start_time, b.name as number_name, c.name as property_name')
            ->order(['a.start_time' => 'asc'])
            ->limit(1)
            ->select()
            ->toArray();
        if (count($bill) == 0) {
            return $this->returnError('无账单');
        }

        // 获取用户openid
        $code = $this->request->post('code/s', '', 'trim');
        try {
            $ch = curl_init();
            // 构建包含查询字符串的URL
            $url = 'https://api.weixin.qq.com/sns/jscode2session?' . http_build_query([
                'appid' => env('WECHAT_APP_ID'),
                'secret' => env('WECHAT_APP_SECRET'),
                'js_code' => $code,
                'grant_type' => 'authorization_code'
            ]);

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);

            if ($response === false) {
                return $this->returnSuccess(curl_error($ch));
            }
            curl_close($ch);
            $result = json_decode($response, true);
            if (isset($result['openid'])) {
                $data = [
                    'thing1' => $bill[0]['property_name'] . '-' . $bill[0]['number_name'],
                    'time2' => $bill[0]['start_time'],
                ];

                // 查询是否存在记录
                $subscribeRecord = SubscribeModel::where('open_id', $result['openid'])->find();
                if ($subscribeRecord) {
                    // 记录存在，执行更新操作
                    $subscribeRecord->save($data);
                } else {
                    // 记录不存在，执行新增操作
                    $data['open_id'] = $result['openid'];
                    SubscribeModel::create($data);
                }

                return $this->returnSuccess('订阅成功');
            } else {
                return $this->returnError('订阅失败');
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }
}
