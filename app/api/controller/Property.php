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

    // hearder查询全部房产
    public function queryPropertyAll()
    {
        $loginUser = $this->auth->getLoginUser();
        $property = PropertyModel::where('admin_user_id', $loginUser['id'])
        ->field('id as value,name as label, firstly')
        ->order('firstly, id')
        ->select()
        ->toArray();
        array_unshift($property, ['value' => 0, 'label' => '全部房产', 'firstly' => 'N' ]);
        return $this->returnWechat($property);
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
                return $this->returnError('房产不存在');
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
