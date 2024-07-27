<?php

namespace app\admin\controller\admin;

use app\admin\controller\Common;
use app\admin\model\AdminUser as UserModel;
use app\admin\model\AdminRole as RoleModel;
use app\admin\validate\AdminUser as UserValidate;
use think\facade\View;

class User extends Common
{
    public function index()
    {
        return View::fetch();
    }

    public function query()
    {
        $user = UserModel::with('adminRole')->withoutField(['password', 'salt'], true)->select()->toArray();
        $user = \array_map(function ($item) {
            return array(
                'id' => $item["id"],
                'admin_role_id' => $item["admin_role_id"],
                'username' => $item["username"],
                'role_name' => $item["adminRole"]["name"],
                'login_date' => $item["login_date"],
                'expiration_date' => $item["expiration_date"] ? \substr($item["expiration_date"], 0, 10) : '',
                'create_time' =>  \substr($item["create_time"], 0, 10),
                'update_time' => $item["update_time"] ? \substr($item["update_time"], 0, 10) : '',
                'state' => strtotime($item["expiration_date"]) > time() ? 'Y' : 'N',
            );
        }, $user);
        return $this->returnElement($user);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'username' => $this->request->post('username/s', '', 'trim'),
            'admin_role_id' => $this->request->post('admin_role_id/d', 0),
            'expiration_date' => $this->request->post('expiration_date/s', '', 'trim'),
            'password' => $this->request->post('password/s', '', 'trim')
        ];
        if ($id && $data['password'] === '') {
            unset($data['password']);
        }
        $validate = new UserValidate();
        if ($id) {
            if (!$validate->scene('update')->check(\array_merge($data, ['id' => $id]))) {
                $this->error('修改失败，' . $validate->getError() . '。');
            }
            if (!$user = UserModel::find($id)) {
                $this->error('修改失败，记录不存在。');
            }
            $user->save($data);
            $this->success('修改成功。');
        }
        if (!$validate->scene('insert')->check($data)) {
            $this->error('添加失败,' . $validate->getError() . '。');
        }
        UserModel::create($data);
        $this->success('添加成功。');
    }

    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        if (!$user = UserModel::find($id)) {
            $this->error('删除失败，记录不存在。');
        }
        $user->delete();
        $this->success('删除成功。');
    }
}
