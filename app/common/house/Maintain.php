<?php

namespace app\common\house;

use app\admin\model\TenantMaintain as MaintainModel;
use app\admin\model\HouseOther as OtherModel;
use app\admin\model\BillSum as SumModel;

class Maintain
{
    public static function handle($id = 0, $total_money = 0, $admin_user_id = 0)
    {
        if (!$maintain_data = MaintainModel::alias('a')
            ->join('HouseNumber b', 'a.house_number_id = b.id')
            ->field('a.*, b.name as number_name')
            ->find($id)) {
            return ['flag' => false, 'msg' => '维修单不存在'];
        }
        $maintain_data->save(['status' => 'Y']);
        if ($total_money > 0) {
            $data = [
                'house_property_id' => $maintain_data->house_property_id,
                'type' => 'D',
                'total_money' => $total_money,
                'note' => $maintain_data->number_name . '-' . $maintain_data->description,
                'accout_mark' => 'Y',
                'accounting_date' => date('Y-m-d'),
            ];
            OtherModel::create($data);
            // 更新收支汇总表金额字段
            $accounting_month = date('Y-m');
            $sum_data = SumModel::where([
                'house_property_id' => $maintain_data->house_property_id,
                'type' => TYPE_EXPENDITURE,
                'accounting_date' => $accounting_month,
            ])->find();
            if ($sum_data) {
                $sum_data->save([
                    'amount' => $sum_data->amount + $total_money,
                ]);
            } else {
                SumModel::create([
                    'admin_user_id' => $admin_user_id,
                    'house_property_id' => $maintain_data->house_property_id,
                    'amount' => $total_money,
                    'type' => TYPE_EXPENDITURE,
                    'accounting_date' => $accounting_month,
                    'annual' => date('Y'),
                ]);
            }
        }
        return ['flag' => true, 'msg' => '处理完成'];
    }
}
