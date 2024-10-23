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
            // 'address' => $this->request->post('address/s', '', 'trim'),
        ];
        $loginUser = $this->auth->getLoginUser();
        $result = PropertyAction::save($id, $data, $loginUser['id']);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
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
}
