<?php

namespace app\common\house;

use app\admin\model\HouseProperty as PropertyModel;

class Property
{
    public static function save($id, $data)
    {
        $loginUser = $this->auth->getLoginUser();
        if ($id) {
            if (!$property = PropertyModel::find($id)) {
                return ['flag' => false, 'msg' => '房产不存在'];
            }
            if (PropertyModel::where('name', $data['name'])
                    ->where('id', '<>', $id)
                    ->where('admin_user_id', $loginUser['id'])
                    ->find()) {
                return ['flag' => false, 'msg' => '房间名已存在'];
            }
            $property->save($data);
            return ['flag' => true, 'msg' => '修改成功'];
        }
        if (PropertyModel::where('name', $data['name'])->where('admin_user_id', $loginUser['id'])->find()) {
            return ['flag' => false, 'msg' => '房间名已存在'];
        }
        $data['admin_user_id'] = $loginUser['id'];
        $data['firstly'] = 'Y';
        PropertyModel::where('admin_user_id', $loginUser['id'])->update(['firstly' => 'N']);
        PropertyModel::create($data);
        return ['flag' => true, 'msg' => '添加成功'];
    }
}
