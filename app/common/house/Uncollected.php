<?php

namespace app\common\house;

use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\HouseBilling as BillingModel;
use app\admin\model\BillSum as SumModel;
use app\admin\model\WeMeter as MeterModel;
use app\admin\model\WeDetail as WeDetailModel;
use app\admin\library\Date;
use think\facade\Db;

class Uncollected
{
    public static function save($id, $data)
    {
        if (!$billing_data = BillingModel::find($id)) {
            // 新增账单
            $number_data = NumberModel::where('house_property_id', $data['house_property_id'])
                ->where('id', $data['house_number_id'])
                ->find();
            $data['electricity_consumption'] = $data['electricity_meter_this_month'] - $data['electricity_meter_last_month'];
            $data['electricity'] = $data['electricity_consumption'] * $number_data->electricity_price * $number_data->ratio;
            $data['water_consumption'] = $data['water_meter_this_month'] - $data['water_meter_last_month'];
            $data['water'] = $data['water_consumption'] * $number_data->water_price * $number_data->ratio;
            $data['total_money'] = round($data['water'] + $data['electricity'] + $data['rental'] + $data['deposit']
                + $data['management'] + $data['network'] + $data['garbage_fee'] + $data['other_charges'], 2);
            BillingModel::create($data);
            return ['flag' => true, 'msg' => '添加成功'];
        } else {
            if (isset($data['end_time'])) {
                unset($data['end_time']);
            }
            $number_data = NumberModel::where('house_property_id', $billing_data->house_property_id)
                ->where('id', $billing_data->house_number_id)
                ->find();
            $data['electricity_consumption'] = $data['electricity_meter_this_month'] - $data['electricity_meter_last_month'];
            $data['electricity'] = $data['electricity_consumption'] * $number_data->electricity_price * $number_data->ratio;
            $data['water_consumption'] = $data['water_meter_this_month'] - $data['water_meter_last_month'];
            $data['water'] = $data['water_consumption'] * $number_data->water_price * $number_data->ratio;
            $data['total_money'] = round($data['water'] + $data['electricity'] + $data['rental'] + $data['deposit']
                + $data['management'] + $data['network'] + $data['garbage_fee'] + $data['other_charges'], 2);
            $billing_data->save($data);
            return ['flag' => true, 'msg' => '修改成功'];
        }
    }

    //到账
    public static function account($id, $admin_user_id)
    {
        if (!$billing_data = BillingModel::find($id)) {
            return ['flag' => false, 'msg' => '到账失败，账单不存在'];
        }
        $oldBill = clone $billing_data;
        $number_data = NumberModel::find($billing_data->house_number_id);
        $transFlag = true;
        Db::startTrans();
        try {
            if ($number_data->rent_mark === 'Y') {
                $billing_update['accounting_date'] = date('Y-m-d', time());
                $billing_data->save($billing_update);
                if ($billing_data->end_time && $billing_data->id == $number_data->receipt_number) {
                    $dates = Date::getLease($number_data->checkin_time, $number_data->lease, $number_data->lease_type);
                    $billing_insert = [
                        'house_property_id' => $billing_data['house_property_id'],
                        'house_number_id' => $billing_data['house_number_id'],
                        'start_time' => $dates[0],
                        'end_time' => $dates[1],
                        'tenant_id' => $number_data['tenant_id'],
                        'rental' => $number_data['rental'] * $number_data['lease_type'],
                        'management' => $number_data['management'] * $number_data['lease_type'],
                        'network' => $number_data['network'] * $number_data['lease_type'],
                        'garbage_fee' => $number_data['garbage_fee'] * $number_data['lease_type'],
                        'electricity_meter_last_month' => $billing_data['electricity_meter_this_month'],
                        'water_meter_last_month' => $billing_data['water_meter_this_month'],
                        'total_money' => ($number_data['rental'] + $number_data['management']  + $number_data['network'] + $number_data['garbage_fee']) * $number_data['lease_type'],
                    ];
                    $new_billing = BillingModel::create($billing_insert);
                    $number_update['payment_time'] = $billing_insert['start_time'];
                    $number_update['receipt_number'] = $new_billing['id'];
                    $number_update['lease'] = $number_data['lease'] + $number_data['lease_type'];
                    $number_data->save($number_update);
                }
            } else {
                //退房清算
                $billing_update['accounting_date'] = date('Y-m-d', time());
                $billing_data->save($billing_update);
                $number_update = [
                    'payment_time' => null,
                    'receipt_number' => '',
                ];
                $number_data->save($number_update);
            }
            //总表记录
            $accounting_month = date('Y-m', time());
            $sum_data = SumModel::where([
                'house_property_id' => $oldBill->house_property_id,
                'type' => TYPE_INCOME,
                'accounting_date' => $accounting_month,
            ])->find();
            if ($sum_data) {
                $sum_data->save([
                    'amount' => $sum_data->amount + $oldBill['total_money'],
                ]);
            } else {
                SumModel::create([
                    'admin_user_id' =>  $admin_user_id,
                    'house_property_id' => $oldBill->house_property_id,
                    'amount' => $oldBill->total_money,
                    'type' => TYPE_INCOME,
                    'accounting_date' => $accounting_month,
                    'annual' => date('Y'),
                ]);
            }
            // 新增水电表记录
            if ($oldBill->electricity) {
                $electricity = MeterModel::where('house_property_id', $oldBill->house_property_id)
                    ->where('type', TYPE_ELECTRICITY)
                    ->whereFindInSet('house_number_id', $oldBill->house_number_id)
                    ->find();
                if ($electricity) {
                    WeDetailModel::create([
                        'meter_id' => $electricity->id,
                        'house_property_id' => $oldBill->house_property_id,
                        'amount' => $oldBill->electricity,
                        'dosage' => $oldBill->electricity_consumption,
                        'type' => TYPE_ELECTRICITY,
                        'calculate_date' => date("Y-m-d", strtotime("-1 month", strtotime($oldBill->start_time)))
                    ]);
                }
            }
            if ($oldBill->water) {
                $water = MeterModel::where('house_property_id', $oldBill->house_property_id)
                    ->where('type', TYPE_WATER)
                    ->whereFindInSet('house_number_id', $oldBill->house_number_id)
                    ->find();
                if ($water) {
                    WeDetailModel::create([
                        'meter_id' => $water->id,
                        'house_property_id' => $oldBill->house_property_id,
                        'amount' => $oldBill->water,
                        'dosage' => $oldBill->water_consumption,
                        'type' => TYPE_WATER,
                        'calculate_date' => date("Y-m-d", strtotime("-1 month", strtotime($oldBill->start_time)))
                    ]);
                }
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            Db::rollback();
            // 回滚事务
            return ['flag' => false, 'msg' => $e->getMessage()];
        }
        if ($transFlag) {
            return ['flag' => true, 'msg' => '到账成功'];
        }
    }

    public static function saveCentralized($data, $type)
    {
        foreach ($data as $value) {
            if (count($value) > 0) {
                if (!$billing = BillingModel::find($value['id'])) {
                    return ['flag' => false, 'msg' => '修改失败，账单不存在'];
                }
                $number_data = NumberModel::where('house_property_id', $billing->house_property_id)
                    ->where('id', $billing->house_number_id)
                    ->find();
                $data = array();
                $data['meter_reading_time'] = date('Y-m-d', time());
                if ($type == TYPE_ELECTRICITY) {
                    $data['electricity_meter_this_month'] = $value['value'];
                    $data['electricity_consumption'] = $value['value'] - $billing['electricity_meter_last_month'];
                    $data['electricity'] = $data['electricity_consumption'] * $number_data->electricity_price * $number_data->ratio;
                    $data['total_money'] = round($billing['water'] + $data['electricity'] + $billing['rental']
                        + $billing['deposit'] + $billing['other_charges'] + $billing['management'] + $billing['network'] + $billing['garbage_fee'], 2);
                    $billing->save($data);
                } elseif ($type == TYPE_WATER) {
                    $data['water_meter_this_month'] = $value['value'];
                    $data['water_consumption'] = $value['value'] - $billing['water_meter_last_month'];
                    $data['water'] = $data['water_consumption'] * $number_data->water_price * $number_data->ratio;
                    $data['total_money'] = round($data['water'] + $billing['electricity'] + $billing['rental']
                        + $billing['deposit'] + $billing['other_charges'] + $billing['management'] + $billing['network'] + $billing['garbage_fee'], 2);
                    $billing->save($data);
                }
            }
        }
        return ['flag' => true, 'msg' => '修改成功'];
    }
}
