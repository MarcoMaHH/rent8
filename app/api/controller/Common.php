<?php

namespace app\api\controller;

use app\api\library\Auth;
use app\BaseController;

// billMeter类型
//类型-电费
define('TYPE_ELECTRICITY', 'B');
//类型-水费
define('TYPE_WATER', 'C');

// billSum类型
//类型-收入
define('TYPE_INCOME', 'I');
//类型-支出
define('TYPE_EXPENDITURE', 'E');

class Common extends BaseController
{
    protected $auth;
    protected $user;
    protected $checkLoginExclude = [];

    protected function initialize()
    {
        $this->auth = Auth::getInstance();
        $action = $this->request->action();
        $controller = $this->request->controller();
        if (in_array($action, $this->checkLoginExclude)) {
            return;
        }
        if (!$this->auth->isLogin()) {
            echo json_encode(['code' => 999, 'msg' => '您尚未登录']);
            exit;
        }
        if (!$this->auth->checkAuth($controller, $action)) {
            echo json_encode(['code' => 888, 'msg' => '您没有操作权限']);
            exit;
        }
        $this->user = $this->auth->getLoginUser();
    }

    protected function returnWechat($data = [], $count = 0, $msg = '')
    {
        if (!$count) {
            $count = \count($data);
        }
        $data = [
            "code" => 1,
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
