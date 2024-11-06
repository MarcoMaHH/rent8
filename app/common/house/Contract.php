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
}
