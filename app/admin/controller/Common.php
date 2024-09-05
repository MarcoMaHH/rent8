<?php

namespace app\admin\controller;

use app\admin\library\Auth;
use app\BaseController;
// use think\facade\Validate;
use think\facade\Session;
use think\facade\View;

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
                $this->error('令牌已过期，请重新提交。');
            }
        }
        $this->auth = Auth::getInstance();
        $controller = $this->request->controller();
        $action = $this->request->action();
        if (in_array($action, $this->checkLoginExclude)) {
            return;
        }
        if (!$this->auth->isLogin()) {
            $this->error('您还没有登录。', 'Index/login');
        }
        if (!$this->auth->checkAuth($controller, $action)) {
            $this->error('您没有权限访问。');
        }
        $loginUser = $this->auth->getLoginUser();
        View::assign('layout_login_user', ['id' => $loginUser['id'], 'username' => $loginUser['username'], 'expiration_date' => $loginUser['expiration_date']]);
        if (!$this->request->isAjax()) {
            // // 开启模板布局
            // View::config(['layout_on' => true]);
            // // 布局文件名称
            // View::config(['layout_name' => 'common/layout']);
            View::assign('layout_menu', $this->auth->menu($controller));
            View::assign('current_route', $this->auth->currentRoute($controller));
            View::assign('layout_token', $this->getToken());
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

    public function returnElement($data = [], $count = 0, $msg = '', $code = 1)
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
}
