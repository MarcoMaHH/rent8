<?php

namespace app\api\controller;

use app\common\house\Property as PropertyAction;
use app\admin\model\HouseProperty as PropertyModel;

class Property extends Common
{
    public function queryProperty()
    {
        $loginUser = $this->auth->getLoginUser();
        $properties = PropertyModel::where('admin_user_id', $loginUser['id'])
        ->order('id')
        ->select();
        return $this->returnWechat($properties);
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

    public function edit()
    {
        $id = $this->request->param('id/d', 0);
        if ($id) {
            if (!$data = PropertyModel::find($id)) {
                return $this->returnError('记录不存在。');
            }
        }
        $returnData = [
            "code" => 1,
            "data" => $data
        ];
        return \json($returnData);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'name' => $this->request->post('name/s', '', 'trim'),
            'address' => $this->request->post('address/s', '', 'trim'),
        ];
        if ($id) {
            if (!$role = PropertyModel::find($id)) {
                return $this->returnError('修改失败，记录不存在');
            }
            $role->save($data);
            return $this->returnSuccess('修改成功');
        }
        $loginUser = $this->auth->getLoginUser();
        $data['admin_user_id'] = $loginUser['id'];
        PropertyModel::create($data);
        return $this->returnSuccess('添加成功');
    }
}
