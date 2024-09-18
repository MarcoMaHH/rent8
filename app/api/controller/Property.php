<?php

namespace app\api\controller;

use app\admin\library\Property as PropertyLib;
use app\admin\model\HouseProperty as PropertyModel;

class Property extends Common
{
    public function query()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', PropertyLib::getProperty($loginUser['id']));
        $properties = PropertyModel::where('admin_user_id', $loginUser['id'])
                    ->order('firstly')
                    ->select();
        return $this->returnWechat($properties, 0, $house_property_id);
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
