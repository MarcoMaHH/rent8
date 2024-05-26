<?php

namespace app\admin\library;

use app\admin\model\AdminUser as UserModel;
use app\admin\model\AdminMenu as MenuModel;
use app\admin\model\AdminRole as RoleModel;
use think\facade\Session;

class Auth
{
    protected $error;
    protected $sessionName = 'admin';
    protected static $instance;
    protected $loginUser;

    public function login($username, $password)
    {
        $user = UserModel::where('username', $username)->find();
        if (!$user) {
            $this->setError('用户不存在');
            return false;
        }
        if ($user->password != $this->passwordMD5($password, $user->salt)) {
            $this->setError('用户名或密码不正确');
            return false;
        }
        if (strtotime(date("Y-m-d")) - strtotime($user->expiration_date) > 0) {
            $this->setError('用户于 ' . substr($user->expiration_date, 0, 10) . ' 已过期');
            return false;
        }
        Session::set($this->sessionName, ['id' => $user->id]);
        return true;
    }

    public function setError($error)
    {
        $this->error = $error;
        return $this;
    }

    public function getError()
    {
        return $this->error;
    }

    public function passwordMD5($password, $salt)
    {
        return md5(md5($password) . $salt);
    }

    public static function getInstance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    public function isLogin()
    {
        return Session::has($this->sessionName . '.id') && $this->getLoginUser();
    }

    public function logout()
    {
        Session::delete($this->sessionName);
        return true;
    }

    public function menu($controller)
    {
        $user = $this->getLoginUser();
        $menu = MenuModel::tree();
        $data = $menu->getData();
        $result = [];
        foreach ($user['admin_permission'] as $v) {
            if ($v['controller'] === '*') {
                $result = $data;
                break;
            }
            foreach ($data as $vv) {
                if (strtolower($v['controller']) === strtolower($vv['controller'])) {
                    $result[] = $vv;
                    break;
                }
            }
        }
        return $menu->data($result)->getTree(strtolower($controller));
    }

    public function changePassword($password)
    {
        $id = Session::get($this->sessionName . '.id');
        UserModel::find($id)->save(['password' => $password]);
    }

    public function getLoginUser($field = null)
    {
        if (!$this->loginUser) {
            $id = Session::get($this->sessionName . '.id');
            $this->loginUser = UserModel::with('adminPermission')->find($id);
        }
        return $field ? $this->loginUser[$field] : $this->loginUser;
    }

    public function checkAuth($controller, $action)
    {
        $user = $this->getLoginUser();
        if (!RoleModel::where('state', 'Y')->find($user['admin_role_id'])) {
            return false;
        }
        foreach ($user['admin_permission'] as $v) {
            if ($v['controller'] === '*') {
                return true;
            }
            if (strtolower($v['controller']) === strtolower($controller)) {
                if ($v['action'] === '*') {
                    return true;
                }
                if (in_array($action, explode(',', $v['action']))) {
                    return true;
                }
            }
        }
        return false;
    }

    public function currentRoute($controller)
    {
        $menu = MenuModel::tree();
        $data = $menu->getData();
        return $menu->data($data)->getCurrentRoute(strtolower($controller));
    }
}
