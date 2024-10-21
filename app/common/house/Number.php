<?php

namespace app\common\house;

use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\HouseTenant as TenantModel;
use app\admin\model\HouseBilling as BillingModel;
use think\facade\Db;

class Number
{
    public static function save($id, $data)
    {
        if (!PropertyModel::find($data['house_property_id'])) {
            return ['flag' => false, 'msg' => '房产不存在'];
        }
        if ($id) {
            if (!$number = NumberModel::find($id)) {
                return ['flag' => false, 'msg' => '房间不存在'];
            }
            if (NumberModel::where('name', $data['name'])->where('id', '<>', $id)->where('house_property_id', $data['house_property_id'])->find()) {
                return ['flag' => false, 'msg' => '房间名已存在'];
            }
            $number->save($data);
            return ['flag' => true, 'msg' => '修改成功'];
        } else {
            if (NumberModel::where('name', $data['name'])->where('house_property_id', $data['house_property_id'])->find()) {
                return ['flag' => false, 'msg' => '房间名已存在'];
            }
            NumberModel::create($data);
            return ['flag' => true, 'msg' => '添加成功'];
        }
    }

    public static function checkin($data)
    {
        $checkin_time = $data['checkin_time'];
        $data['name'] = $checkin_time . '入住租客';
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
                'network' => $number_data['network'] * $lease_type,
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

    public static function checkout($number_id, $leave_time)
    {
        if (!$number_data = NumberModel::find($number_id)) {
            return ['flag' => false, 'msg' => '退房失败，房间不存在'];
        }
        $transFlag = true;
        Db::startTrans();
        try {
            $number_update = [
                'rent_mark' => 'N',
                'tenant_id' => '',
                'checkin_time' => null,
                'lease' => 0,
            ];
            $number_data->save($number_update);
            TenantModel::where('house_property_id', $number_data->house_property_id)
            ->where('house_number_id', $number_id)
            ->where('leave_time', 'null')
            ->data(['leave_time' => $leave_time, 'mark' => 'Y'])
            ->update();
            $billing_data = BillingModel::find($number_data->receipt_number);
            $datediff = intval((strtotime($leave_time) - strtotime($billing_data->start_time)) / (60 * 60 * 24));
            $note = '';
            $rental = 0;
            if ($datediff > 0) {
                $rental = $datediff * $number_data->daily_rent;
                $note = '租金为' . $datediff . '*' . $number_data->daily_rent . '=' . $rental . '。';
            }
            $billing_update = [
                'start_time' => $leave_time,
                'meter_reading_time' => $leave_time,
                'end_time' => null,
                'rental' => $rental,
                'deposit' => 0 - $number_data->deposit,
                'management' => 0,
                'network' => 0,
                'garbage_fee' => 0,
                'note' => $note,
            ];
            $billing_data->save($billing_update);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            // 回滚事务
            Db::rollback();
            return ['flag' => false, 'msg' => $e->getMessage()];
        }
        if ($transFlag) {
            return ['flag' => true, 'msg' => '退房成功'];
        }
    }
}
