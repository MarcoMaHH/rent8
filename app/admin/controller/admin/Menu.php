<?php

namespace app\admin\controller\admin;

use app\admin\model\AdminMenu as MenuModel;
use app\admin\controller\Common;
use app\admin\validate\AdminMenu as MenuValidate;
use think\facade\View;

class Menu extends Common
{
    public function index()
    {
        return View::fetch('/admin/menu/index');
    }

    public function query()
    {
        $menu = MenuModel::tree()->getTreeListEle();
        return $this->returnResult($menu);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'pid' => $this->request->post('pid/d', 0),
            // 'sort' => $this->request->post('sort/d', 0),
            'name' => $this->request->post('name/s', ''),
            'icon' => $this->request->post('icon/s', ''),
            'controller' => $this->request->post('controller/s', '', 'trim'),
        ];
        $validate = new MenuValidate();
        if ($id) {
            if (!$validate->scene('update')->check(\array_merge($data, ['id' => $id]))) {
                return $this->returnError('修改失败，' . $validate->getError() . '。');
            }
            if (!$menu = MenuModel::find($id)) {
                return $this->returnError('修改失败,记录不存在');
            }
            $menu->save($data);
            return $this->returnSuccess('修改成功。');
        }
        if (!$validate->scene('insert')->check($data)) {
            return $this->returnError('添加失败，' . $validate->getError() . '。');
        }
        MenuModel::create($data);
        return $this->returnSuccess('添加成功');
    }

    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        $validate = new MenuValidate();
        if (!$validate->scene('delete')->check(['id' => $id])) {
            return $this->returnError('删除失败，' . $validate->getError() . '。');
        }
        if (!$menu = MenuModel::find($id)) {
            return $this->returnError('删除失败，记录不存在。');
        }
        $menu->delete();
        return $this->returnSuccess('删除成功。');
    }
}
