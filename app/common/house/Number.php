<?php

namespace app\common\house;

use app\admin\validate\HouseNumber as NumberValidate;
use app\admin\model\HouseNumber as NumberModel;

class Number
{
    public static function save($id, $data)
    {
        $validate = new NumberValidate();
        if (!$validate->scene('update')->check($data)) {
            return ['flag' => false, 'msg' => '修改失败，' . $validate->getError()];
        }
        if (!$number = NumberModel::find($id)) {
            return ['flag' => false, 'msg' => '修改失败，房间不存在'];
        }
        $number->save($data);
        return ['flag' => true, 'msg' => '修改成功'];
    }
}
