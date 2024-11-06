<?php

namespace app\common\house;

use app\admin\model\HouseContract as ContractModel;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\validate\HouseProperty as PropertyValidate;

class Contract
{
    public static function save($id, $data)
    {
        if ($id) {
            if (!$contract = ContractModel::find($id)) {
                return ['flag' => false, 'msg' => '合同不存在'];
            }
            $contract->save($data);
            return ['flag' => true, 'msg' => '修改成功'];
        }
        ContractModel::create($data);
        return ['flag' => true, 'msg' => '添加成功'];
    }

    public static function delete($id)
    {
        if (!$property = PropertyModel::find($id)) {
            return ['flag' => false, 'msg' => '删除失败，房产不存在'];
        }
        $validate = new PropertyValidate();
        if (!$validate->scene('delete')->check(['id' => $id])) {
            return ['flag' => false, 'msg' => '删除失败，' . $validate->getError()];
        }
        $property->delete();
        return ['flag' => true, 'msg' => '删除成功'];
    }
}
