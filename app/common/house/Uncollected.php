<?php

namespace app\common\house;

use app\api\validate\User as UserValidate;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\HouseBilling as BillingModel;
use app\admin\model\BillSum as SumModel;
use app\admin\model\BillMeter as MeterModel;
use app\admin\model\BillHydroelectricity as HydroelectricityModel;
use app\admin\library\Property;
use app\admin\library\Date;
use think\facade\Db;
use think\facade\Log;

class Uncollected
{
    //到账
    public function account()
    {
        $id = $this->request->param('id/d', 0);
        if (!$billing_data = BillingModel::find($id)) {
            return $this->returnError('记录不存在。');
        }
        $oldBill = clone $billing_data;
        $number_data = NumberModel::find($billing_data->house_number_id);
        $transFlag = true;
        Db::startTrans();
        try {
            if ($number_data->rent_mark === 'Y') {
                $billing_update['accounting_date'] = date('Y-m-d', time());
                $billing_data->save($billing_update);
                if ($billing_data->end_time) {
                    $dates = Date::getLease($number_data->checkin_time, $number_data->lease, $number_data->lease_type);
                    $billing_insert = [
                        'house_property_id' => $billing_data['house_property_id'],
                        'house_number_id' => $billing_data['house_number_id'],
                        'start_time' => $dates[0],
                        'end_time' => $dates[1],
                        'tenant_id' => $number_data['tenant_id'],
                        'rental' => $number_data['rental'] * $number_data['lease_type'],
                        'management' => $number_data['management'] * $number_data['lease_type'],
                        'garbage_fee' => $number_data['garbage_fee'] * $number_data['lease_type'],
                        'electricity_meter_last_month' => $billing_data['electricity_meter_this_month'],
                        'water_meter_last_month' => $billing_data['water_meter_this_month'],
                        'total_money' => ($number_data['rental'] + $number_data['management'] + $number_data['garbage_fee']) * $number_data['lease_type'],
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
                    'admin_user_id' => $this->auth->getLoginUser()['id'],
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
            return $this->returnError($e->getMessage());
        }
        if ($transFlag) {
            return $this->returnSuccess('操作成功');
        }
    }
}
