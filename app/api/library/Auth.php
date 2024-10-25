<?php

namespace app\api\library;

use app\admin\model\AdminUser as UserModel;
use app\admin\model\AdminRole as RoleModel;
use think\facade\Session;

class Auth
{
    protected static $instance;
    protected $sessionName = 'wechat';
    protected $loginUser;
    protected $error;

    public static function getInstance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
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

    public function salt()
    {
        return md5(microtime(true));
    }

    public function passwordMD5($password, $salt)
    {
        return md5(md5($password) . $salt);
    }

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
            $this->setError('用户账号于 ' . substr($user->expiration_date, 0, 10) . ' 到期');
            return false;
        }
        Session::set($this->sessionName, ['id' => $user->id]);
        return [
            'session_id' => Session::getId(),
            'id' => $user->id,
            'name' => $user->username,
            'role' => $user->admin_role_id,
            'expiration_date' => substr($user->expiration_date, 0, 10),
        ];
    }

    public function loginWechat($openid)
    {
        $user = UserModel::where('openid', $openid)->find();
        if (!$user) {
            $this->setError('不存在该微信对应的账号');
            return false;
        }
        if (strtotime(date("Y-m-d")) - strtotime($user->expiration_date) > 0) {
            $this->setError('用户账号于 ' . substr($user->expiration_date, 0, 10) . ' 到期');
            return false;
        }
        Session::set($this->sessionName, ['id' => $user->id]);
        $user->save(['login_date' => date("Y-m-d H:i:s")]);
        return [
            'session_id' => Session::getId(),
            'id' => $user->id,
            'name' => $user->username,
            'role' => $user->admin_role_id,
            'expiration_date' => $user->expiration_date,
        ];
    }

    protected function getSession()
    {
        if ($id = request()->header('Authorization')) {
            // session_write_close();
            Session::setId($id);
            Session::init();
        }
        return Session::get($this->sessionName . '.id');
    }

    public function getLoginUser($field = null)
    {
        $id = $this->getSession();
        if (!$this->loginUser && $id) {
            $this->loginUser = UserModel::where('id', $id)->find();
        }
        return $field ? $this->loginUser[$field] : $this->loginUser;
    }

    public function isLogin()
    {
        return $this->getLoginUser();
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

    public function logout()
    {
        Session::delete($this->sessionName);
        return true;
    }

    public function bindWechat($openid)
    {
        $loginUser = $this->getLoginUser();
        $user = UserModel::find($loginUser['id']);
        if ($user->openid) {
            $this->setError('该账号已绑定微信');
            return false;
        }
        $openidSearch = UserModel::where('openid', $openid)->find();
        if ($openidSearch) {
            $this->setError('该微信已绑定账号');
            return false;
        }
        $data = ['openid' => $openid];
        $user->save($data);
        return true;
    }
}
