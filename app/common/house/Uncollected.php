<?php

namespace app\common\house;

use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\HouseFee as FeeModel;
use app\admin\model\HouseReceipt as ReceiptModel;
use app\admin\model\BillSum as SumModel;
use app\admin\model\WeMeter as MeterModel;
use app\admin\model\WeDetail as WeDetailModel;
use app\admin\library\Date;
use think\facade\Db;

class Uncollected
{
    public static function save($id, $data, $admin_user_id)
    {
        if (!$recepit_data = ReceiptModel::find($id)) {
            return ['flag' => false, 'msg' => '修改失败，账单不存在'];
        } else {
            $number_data = NumberModel::find($data['house_number_id']);
            $data['electricity_consumption'] = $data['electricity_meter_this_month'] - $data['electricity_meter_last_month'];
            $data['electricity'] = $data['electricity_consumption'] * $number_data->electricity_price;
            $data['water_consumption'] = $data['water_meter_this_month'] - $data['water_meter_last_month'];
            $data['water'] = $data['water_consumption'] * $number_data->water_price;
            $free = FeeModel::where('admin_user_id', $admin_user_id)->select()->toArray();
            foreach ($free as $v) {
                if (!$v['column_name']) {
                    $data[$v['column_id']] = 0;
                }
            }
            $data['total_money'] = round($data['water'] + $data['electricity'] + $data['rental'] + $data['deposit']
                + $data['fees1'] + $data['fees2'] + $data['fees3'] + $data['fees4'], 2);
            $recepit_data->save($data);
            $number_data->save(['payment_date' => $data['start_time']]);
            return ['flag' => true, 'msg' => '修改成功'];
        }
    }

    //到账
    public static function account($id, $admin_user_id)
    {
        if (!$receipt_data = ReceiptModel::find($id)) {
            return ['flag' => false, 'msg' => '到账失败，账单不存在'];
        }
        $oldBill = clone $receipt_data;
        $number_data = NumberModel::find($receipt_data['house_number_id']);
        $transFlag = true;
        Db::startTrans();
        try {
            if ($receipt_data->bill_type === config('params.bill_types')['退房账单']) {
                //退房清算
                $receipt_update['accounting_date'] = date('Y-m-d', time());
                $receipt_data->save($receipt_update);
                $number_update = [
                    'checkin_date' => null,
                    'payment_date' => null,
                    'calculate_date' => $receipt_update['accounting_date'],
                    'lease' => 0,
                    'house_receipt_id' => null,
                ];
                $number_data->save($number_update);
            } else {
                $receipt_update['accounting_date'] = date('Y-m-d', time());
                $receipt_data->save($receipt_update);
                if ($receipt_data->bill_type === config('params.bill_types')['房租账单']) {
                    $dates = Date::getLease($number_data->calculate_date, $number_data->cycle, $number_data->cycle_type, $number_data->lease);
                    $nextDate = date('Y-m-d', strtotime("$receipt_data->end_time +1 day"));
                    $receipt_insert = [
                        'bill_type' => $receipt_data['bill_type'],
                        'house_property_id' => $receipt_data['house_property_id'],
                        'house_number_id' => $receipt_data['house_number_id'],
                        'tenant_message_id' => $receipt_data['tenant_message_id'],
                        'rental' => $number_data['rental'],
                        'ratio' => $number_data['ratio'],
                        'fees1' => $number_data['fees1'],
                        'fees2' => $number_data['fees2'],
                        'fees3' => $number_data['fees3'],
                        'fees4' => $number_data['fees4'],
                        'electricity_meter_last_month' => $receipt_data['electricity_meter_this_month'],
                        'water_meter_last_month' => $receipt_data['water_meter_this_month'],
                        'total_money' => $number_data['rental'] + $number_data['fees1']  + $number_data['fees2'] + $number_data['fees3'] + $number_data['fees4'],
                    ];
                    if ($dates[0] ===  $nextDate) {
                        $receipt_insert['start_time'] = $dates[0];
                        $receipt_insert['end_time'] = $dates[1];
                        $number_update['lease'] = $number_data['lease'] + 1;
                    } else {
                        // 计算日期改变了，重新计算账单周期和起始时间
                        $receipt_insert['start_time'] = $nextDate;
                        $receipt_insert['end_time'] = Date::getLease($nextDate, $number_data->cycle, $number_data->cycle_type, 0)[1];
                        $number_update['calculate_date'] = $nextDate;
                        $number_update['lease'] = 1;
                    }
                    $receipt_id = ReceiptModel::create($receipt_insert);
                    $number_update = [
                        'payment_date' => $receipt_insert['start_time'],
                        'house_receipt_id' => $receipt_id->id,
                    ];
                    $number_data->save($number_update);
                }
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
                if (!$recepit_data = ReceiptModel::find($value['id'])) {
                    return ['flag' => false, 'msg' => '修改失败，账单不存在'];
                }
                $number_data = NumberModel::where('house_receipt_id', $recepit_data->id)->find();
                $update_data = array();
                $update_data['meter_reading_time'] = date('Y-m-d', time());
                if ($type == TYPE_ELECTRICITY) {
                    $update_data['electricity_meter_this_month'] = $value['value'];
                    $update_data['electricity_consumption'] = $value['value'] - $recepit_data['electricity_meter_last_month'];
                    $update_data['electricity'] = $update_data['electricity_consumption'] * $number_data->electricity_price;
                    $update_data['total_money'] = round($recepit_data['water'] + $update_data['electricity'] + $recepit_data['rental']
                        + $recepit_data['deposit'] + $recepit_data['fees1'] + $recepit_data['fees2'] + $recepit_data['fees3'] + $recepit_data['fees4'], 2);
                    $recepit_data->save($update_data);
                } elseif ($type == TYPE_WATER) {
                    $update_data['water_meter_this_month'] = $value['value'];
                    $update_data['water_consumption'] = $value['value'] - $recepit_data['water_meter_last_month'];
                    $update_data['water'] = $update_data['water_consumption'] * $number_data->water_price;
                    $update_data['total_money'] = round($update_data['water'] + $recepit_data['electricity'] + $recepit_data['rental']
                        + $recepit_data['deposit'] + $recepit_data['fees1'] + $recepit_data['fees2'] + $recepit_data['fees3'] + $recepit_data['fees4'], 2);
                    $recepit_data->save($update_data);
                }
            }
        }
        return ['flag' => true, 'msg' => '修改成功'];
    }
}
