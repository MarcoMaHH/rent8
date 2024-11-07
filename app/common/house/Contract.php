<?php

namespace app\common\house;

use app\admin\model\HouseContract as ContractModel;

class Contract
{
    public static function save($data)
    {
        if (!$contract = ContractModel::find($data['id'])) {
            return ['flag' => false, 'msg' => '合同不存在'];
        }
        $contract->save($data);
        return ['flag' => true, 'msg' => '修改成功'];
    }
}
