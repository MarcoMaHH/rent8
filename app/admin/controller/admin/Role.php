<?php
namespace app\admin\controller\admin;

use app\admin\controller\Common;
use app\admin\model\AdminRole as RoleModel;
use app\admin\validate\AdminRole as RoleValidate;
use think\facade\View;

class Role extends Common
{
    public function index()
    {
        return View::fetch();
    }

    public function query()
    {
        $role = RoleModel::select()->toArray();
        return $this->returnResult($role);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'name' => $this->request->post('name/s', ''),
            'state' => $this->request->post('state/s', ''),
        ];
        if ($id) {
            if (!$role = RoleModel::find($id)) {
                return $this->returnError('修改失败，记录不存在');
            }
            $role->save($data);
            return $this->returnSuccess('修改成功');
        }
        RoleModel::create($data);
        return $this->returnSuccess('添加成功');
    }

    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        if (!$role = RoleModel::find($id, 'adminPermission')) {
            return $this->returnError('删除失败，记录不存在');
        }
        $role->together(['admin_permission'])->delete();
        return $this->returnSuccess('删除成功');
    }

    public function queryRole()
    {
        $role = RoleModel::select()->toArray();
        return $this->returnResult($role);
    }
}
