<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseProperty as PropertyModel;
use app\common\house\Property as PropertyAction;
use think\facade\View;

class Property extends Common
{
    public function index()
    {
        return View::fetch('/house/property/index');
    }
    public function queryProperty()
    {
        $loginUser = $this->auth->getLoginUser();
        $property = PropertyModel::where('admin_user_id', $loginUser['id'])
            ->order('id')
            ->select();
        return $this->returnResult($property);
    }
    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        $result = PropertyAction::delete($id);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    // 编辑房产信息页面
    public function save()
    {
        View::assign('id', $this->request->get('id/d', 0));
        return View::fetch('/house/property/save');
    }
    public function queryPropertyEdit()
    {
        $id = $this->request->param('id/d', 0);
        $data = ['id' => 0];
        if ($id) {
            if (!$data = PropertyModel::find($id)->toArray()) {
                return $this->returnError('房产不存在');
            } else {
                return $this->returnResult($data);
            }
        } else {
            return $this->returnResult($data);
        }
    }
    public function runSave()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'name' => $this->request->post('name/s', null, 'trim'),
            'name_show' => $this->request->post('name_show/s', null, 'trim'),
            'address' => $this->request->post('address/s', null, 'trim'),
            'landlord' => $this->request->post('landlord/s', null, 'trim'),
            'phone' => $this->request->post('phone/s', null, 'trim'),
            'id_card' => $this->request->post('id_card/s', null, 'trim'),
        ];
        $loginUser = $this->auth->getLoginUser();
        $result = PropertyAction::save($id, $data, $loginUser['id']);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    // hearder查询全部房产
    public function queryPropertyAll()
    {
        $loginUser = $this->auth->getLoginUser();
        $property = PropertyModel::where('admin_user_id', $loginUser['id'])
            ->field('id, name, default')
            ->order('default, id')
            ->select()
            ->toArray();
        array_unshift($property, ['id' => 0, 'name' => '全部', 'default' => 'N']);
        return $this->returnResult($property);
    }
    public function sort()
    {
        $id = $this->request->param('id/d', 0);
        $result = PropertyAction::sort($id, $this->auth->getLoginUser()['id']);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }
}
