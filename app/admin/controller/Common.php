<?php

namespace app\admin\controller;

use app\admin\library\Auth;
use app\BaseController;
use think\facade\Session;
use think\facade\View;
use Firebase\JWT\JWT;

//每页显示的条数
define('LAYUI_PAGE', 1);
//每页条数的选择项
define('LAYUI_LIMIT', 10);

// WeMeter水电表类型
//类型-电费
define('TYPE_ELECTRICITY', 'E');
//类型-水费
define('TYPE_WATER', 'W');

// billSum类型
//类型-收入
define('TYPE_INCOME', 'I');
//类型-支出
define('TYPE_EXPENDITURE', 'E');

class Common extends BaseController
{
    protected $auth;
    protected $checkLoginExclude = [];

    public function initialize()
    {
        if ($this->request->isPost()) {
            $token = $this->getToken();
            header('X-CSRF-TOKEN: ' . $token);
            if ($token !== $this->request->header('X-CSRF-TOKEN')) {
                return $this->error('令牌已过期，请重新提交。', '/admin/index/login');
            }
        }
        $this->auth = Auth::getInstance();
        $controller = $this->request->controller();
        $action = $this->request->action();
        if (in_array($action, $this->checkLoginExclude)) {
            return;
        }
        if (!$this->auth->isLogin()) {
            return $this->error('请重新登录', '/admin/index/login');
        }
        if (!$this->auth->checkAuth($controller, $action)) {
            return $this->error('您没有操作权限', '/admin/index/login');
        }
        $loginUser = $this->auth->getLoginUser();
        View::assign('layout_login_user', ['id' => $loginUser['id'], 'username' => $loginUser['username'], 'expiration_date' => $loginUser['expiration_date']]);
        if (!$this->request->isAjax()) {
            View::assign('layout_menu', $this->auth->menu($controller));
            View::assign('current_route', $this->auth->currentRoute($controller));
            View::assign('layout_token', $this->getToken());
            // 获取coze的token
            $appId = env('COZE_APP_ID'); // 替换为您的 APP_ID
            $publicKeyFingerprint = env('COZE_PUBLIC_KEY_FINGERPRINT'); // 替换为您的 PUBLIC_KEY_FINGERPRINT
            $privateKeyFilePath = 'static/private_key.pem'; // 替换为您的私钥文件路径
            $jwt = $this->generateJWT($appId, $publicKeyFingerprint, $privateKeyFilePath);
            $accessToken = $this->getAccessToken($jwt);
            View::assign('coze_token', $accessToken);
            View::assign('coze_botid', env('COZE_BOTID'));
        }
    }

    protected function generateJWT($appId, $publicKeyFingerprint, $privateKeyFilePath)
    {
        $privateKey = file_get_contents($privateKeyFilePath);
        $payload = [
            'iss' => $appId,
            'aud' => 'api.coze.cn',
            'iat' => time(),
            'exp' => time() + 3600, // 令牌过期时间设置为1小时
            'jti' => bin2hex(random_bytes(16)) // 生成随机的 JWT ID
        ];
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
            'kid' => $publicKeyFingerprint
        ];

        // 正确使用 JWT::encode() 方法，传递密钥和算法，以及可选的头部数组
        $jwt = JWT::encode($payload, $privateKey, 'RS256', $publicKeyFingerprint, $header);
        return $jwt;
    }

    protected function getAccessToken($jwt)
    {
        $url = 'https://api.coze.cn/api/permission/oauth2/token';
        $data = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'duration_seconds' => 86399 // 令牌有效期，最大为24小时（86400秒）
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $jwt
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        // if ($response === false) {
        //     die('cURL Error: ' . curl_error($ch));
        // }
        curl_close($ch);

        $responseData = json_decode($response, true);
        if (isset($responseData['access_token'])) {
            return $responseData['access_token'];
        } else {
            die('Failed to get access token: ' . json_encode($responseData));
        }
    }

    public function getToken()
    {
        $token = Session::get('X-CSRF-TOKEN');
        if (!$token) {
            $token = md5(uniqid(microtime(), true));
            Session::set('X-CSRF-TOKEN', $token);
        }
        return $token;
    }

    protected function returnResult($data = [], $count = 0, $msg = '', $code = 1)
    {
        if (!$count) {
            $count = \count($data);
        }
        $data = [
            "code" =>  $code,
            "msg" =>  $msg,
            "count" => $count,
            "data" => $data
        ];
        return \json($data);
    }

    protected function returnError($msg = '系统出错')
    {
        $data = [
            "code" => 0,
            "msg" =>  $msg
        ];
        return \json($data);
    }

    protected function returnSuccess($msg = '操作成功')
    {
        $data = [
            "code" => 1,
            "msg" =>  $msg
        ];
        return \json($data);
    }
}
