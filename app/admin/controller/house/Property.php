<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseProperty as PropertyModel;
use app\common\house\Property as PropertyAction;
use app\admin\validate\HouseProperty as PropertyValidate;
use think\facade\View;

class Property extends Common
{
    public function index()
    {
        return View::fetch();
    }

    public function queryProperty()
    {
        $loginUser = $this->auth->getLoginUser();
        $property = PropertyModel::where('admin_user_id', $loginUser['id'])
        ->order('id')
        ->select();
        return $this->returnResult($property);
    }

    public function queryPropertyAll()
    {
        $loginUser = $this->auth->getLoginUser();
        $property = PropertyModel::where('admin_user_id', $loginUser['id'])
        ->field('id,name,firstly')
        ->order('firstly, id')
        ->select();
        return $this->returnResult($property);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'name' => $this->request->post('name/s', null, 'trim'),
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

    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        if (!$property = PropertyModel::find($id)) {
            return $this->returnError('删除失败，记录不存在。');
        }
        $validate = new PropertyValidate();
        if (!$validate->scene('delete')->check(['id' => $id])) {
            return $this->returnError('删除失败，' . $validate->getError() . '。');
        }
        $property->delete();
        return $this->returnSuccess('删除成功');
    }

    public function sort()
    {
        $id = $this->request->post('id/d', 0);
        $loginUser = $this->auth->getLoginUser();
        $data = PropertyModel::where('admin_user_id', $loginUser['id'])->select()->toArray();
        $result = [];
        foreach ($data as $value) {
            $temp = array(
                'id' => $value['id']
            );
            if ($value['id'] === $id) {
                $temp['firstly'] = 'Y';
            } else {
                $temp['firstly'] = 'N';
            }
            \array_push($result, $temp);
        }
        $property = new PropertyModel();
        $property->saveAll($result);
        return $this->returnSuccess('切换成功');
    }
}
