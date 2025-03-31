<?php
namespace app\admin\controller\admin;

use app\admin\controller\Common;
use app\admin\model\AdminRole as RoleModel;
use app\admin\model\AdminPermission as PermissionModel;
use app\admin\validate\AdminPermission as PermissionValidate;
use think\facade\View;

class Permission extends Common
{
    public function index()
    {
        return View::fetch('/admin/permission/index');
    }

    public function query()
    {
        $admin_role_id = $this->request->param('admin_role_id/d', 0);
        if ($admin_role_id == 0) {
            $roles = RoleModel::select();
            $admin_role_id = \count($roles)>0 ? $roles[0]->id : 0;
        }
        $count = PermissionModel::where('admin_role_id', $admin_role_id)->count();
        $roles = PermissionModel::where('a.admin_role_id', $admin_role_id)
        ->alias('a')
        ->join('AdminRole b', 'a.admin_role_id = b.id')
        ->field('a.*,b.name as role_name')
        ->order('a.id')
        ->select()
        ->toArray();
        return $this->returnResult($roles, $count);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'admin_role_id' => $this->request->post('admin_role_id/d', 0),
            'controller' => $this->request->post('controller/s', '', 'trim'),
            'action' => $this->request->post('action/s', '', 'trim')
        ];
        $validate = new PermissionValidate();
        if ($id) {
            if (!$validate->scene('update')->check($data)) {
                return $this->returnError('修改失败，' . $validate->getError() . '。');
            }
            if (!$permission = PermissionModel::find($id)) {
                return $this->returnError('修改失败，记录不存在。');
            }
            $permission->save($data);
            return $this->returnSuccess('修改成功。');
        }
        if (!$validate->scene('insert')->check($data)) {
            return $this->returnError('添加失败，' . $validate->getError() . '。');
        }
        PermissionModel::create($data);
        return $this->returnSuccess('添加成功。');
    }

    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        if (!$permission = PermissionModel::find($id)) {
            return $this->returnError('删除失败，记录不存在。');
        }
        $permission->delete();
        return $this->returnSuccess('删除成功。');
    }
}
