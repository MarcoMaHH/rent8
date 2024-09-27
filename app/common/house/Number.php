<?php

namespace app\common\house;

use app\admin\validate\HouseNumber as NumberValidate;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\HouseTenant as TenantModel;
use app\admin\model\HouseBilling as BillingModel;
use think\facade\Db;

class Number
{
    public static function save($id, $data)
    {
        if ($id) {
            $validate = new NumberValidate();
            if (!$validate->scene('update')->check($data)) {
                return ['flag' => false, 'msg' => '修改失败，' . $validate->getError()];
            }
            if (!$number = NumberModel::find($id)) {
                return ['flag' => false, 'msg' => '修改失败，房间不存在'];
            }
            $number->save($data);
            return ['flag' => true, 'msg' => '修改成功'];
        } else {
            if (NumberModel::where('name', $data['name'])->where('house_property_id', $data['house_property_id'])->find()) {
                return $this->returnError('房间名已存在');
            }
            NumberModel::create($data);
            return ['flag' => true, 'msg' => '添加成功'];
        }
    }

    public static function checkin($data)
    {
        $checkin_time = $data['checkin_time'];
        if (!$number_data = NumberModel::find($data['house_number_id'])) {
            return ['flag' => false, 'msg' => '入住失败，房间不存在'];
        }
        // 账单资料
        $note = "单据开出中途退房，一律不退房租。 \n" .
                "到期如果不续租，超期将按每天" . $number_data['daily_rent'] . "元计算。" ;
        $lease_type = $number_data['lease_type'];
        $transFlag = true;
        Db::startTrans();
        try {
            //insert租客资料
            $tenant = TenantModel::create($data);
            // 删除上位租客的账单
            BillingModel::where('house_property_id', $data['house_property_id'])
            ->where('house_number_id', $data['house_number_id'])
            ->delete();
            //insert账单资料
            $billing_data = [
                'house_property_id' => $data['house_property_id'],
                'house_number_id' => $data['house_number_id'],
                'start_time' => $data['checkin_time'],
                'end_time' => date('Y-m-d', strtotime("$checkin_time +$lease_type month -1 day")),
                'tenant_id' => $tenant->id,
                'rental' => $number_data['rental'] * $lease_type,
                'deposit' => $number_data['deposit'],
                'management' => $number_data['management'] * $lease_type,
                'garbage_fee' => $number_data['garbage_fee'] * $lease_type,
                'total_money' => $number_data['deposit'] + $number_data['rental'] * $lease_type + $number_data['management'] * $lease_type + $number_data['garbage_fee'] * $lease_type,
                'note' => $note
            ];
            $billing = BillingModel::create($billing_data);
            //update房号资料
            $update_data = [
                'tenant_id' => $tenant->id,
                'receipt_number' => $billing->id,
                'payment_time' => $checkin_time,
                'checkin_time' => $checkin_time,
                'rent_mark' => 'Y',
                'lease' => $lease_type,
            ];
            $number_data->save($update_data);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            // 回滚事务
            Db::rollback();
            return ['flag' => false, 'msg' => $e->getMessage()];
        }
        if ($transFlag) {
            return ['flag' => true, 'msg' => '入住成功'];
        }
    }

    public static function checkout($id)
    {
    }
}
