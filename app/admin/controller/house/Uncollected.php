<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\HouseTenant as TenantModel;
use app\admin\model\HouseBilling as BillingModel;
use app\admin\model\BillSum as SumModel;
use app\admin\model\BillMeter as MeterModel;
use app\admin\model\BillHydroelectricity as HydroelectricityModel;
use app\admin\library\Property;
use app\admin\library\Date;
use think\facade\Db;
use think\facade\View;

class Uncollected extends Common
{
    public function index()
    {
        return View::fetch();
    }

    // 抄表日期选项
    public function queryReadingTime()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $conditions = array(
            ['a.house_property_id', '=', $house_property_id],
            ['a.start_time', '< time', 'today+5 days'],
            ['a.accounting_date', 'null', ''],
        );
        $datas = BillingModel::where($conditions)
        ->alias('a')
        ->join('HouseNumber b', 'b.house_property_id = a.house_property_id and b.id = a.house_number_id')
        ->join('HouseProperty c', 'c.id = a.house_property_id')
        ->distinct(true)
        ->field('a.meter_reading_time')
        ->select();
        $data = [];
        foreach ($datas as $value) {
            if ($value['meter_reading_time']) {
                $value['meter_reading_time'] = \substr($value['meter_reading_time'], 0, 10);
                array_push($data, $value);
            }
        }
        return $this->returnElement($data);
    }

    //主页面 table查询
    public function query()
    {
        $order = $this->request->param('order/s', '', 'trim');
        $field = $this->request->param('field/s', '', 'trim');
        if (!$order) {
            $field = 'a.start_time';
            $order = 'asc';
        }
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $meter_reading_time = $this->request->param('meter_reading_time/s', '', 'trim');
        $conditions = array(
            ['a.house_property_id', '=', $house_property_id],
            ['a.start_time', '< time', 'today+10 days'],
            ['a.accounting_date', 'null', ''],
        );
        if ($meter_reading_time) {
            \array_push($conditions, ['a.meter_reading_time', '=', $meter_reading_time]);
        }
        $count = BillingModel::where($conditions)
        ->alias('a')
        ->count();
        $datas = BillingModel::where($conditions)
        ->alias('a')
        ->join('HouseNumber b', 'b.house_property_id = a.house_property_id and b.id = a.house_number_id')
        ->join('HouseProperty c', 'c.id = a.house_property_id')
        ->field('a.*, b.name, b.water_price, b.electricity_price, c.name as property_name')
        ->order($field, $order)
        ->select();
        foreach ($datas as $value) {
            if ($value['meter_reading_time']) {
                $value['meter_reading_time'] = \substr($value['meter_reading_time'], 0, 10);
            }
            if ($value['start_time']) {
                $value['start_time'] = \substr($value['start_time'], 0, 10);
            }
            if ($value['end_time']) {
                $value['end_time'] = \substr($value['end_time'], 0, 10);
            }
        }
        return $this->returnElement($datas, $count);
    }

    //抄表页面 保存
    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'house_property_id' => $this->request->post('house_property_id/d', 0),
            'house_number_id' => $this->request->post('house_number_id/d', 0),
            'meter_reading_time' => $this->request->post('meter_reading_time/s', '', 'trim'),
            'electricity_meter_this_month' => $this->request->post('electricity_meter_this_month/d', 0),
            'water_meter_this_month' => $this->request->post('water_meter_this_month/d', 0),
            'electricity_meter_last_month' => $this->request->post('electricity_meter_last_month/d', 0),
            'water_meter_last_month' => $this->request->post('water_meter_last_month/d', 0),
            'rental' => $this->request->post('rental/d', 0),
            'deposit' => $this->request->post('deposit/d', 0),
            'management' => $this->request->post('management/d', 0),
            'garbage_fee' => $this->request->post('garbage_fee/d', 0),
            'other_charges' => $this->request->post('other_charges/f', 0),
            'note' => $this->request->post('note/s', '', 'trim'),
        ];
        if (!$billing_data = BillingModel::find($id)) {
            $this->error('记录不存在。');
        }
        $number_data = NumberModel::where('house_property_id', $billing_data->house_property_id)
        ->where('id', $billing_data->house_number_id)
        ->find();
        $data['electricity_consumption'] = $data['electricity_meter_this_month'] - $data['electricity_meter_last_month'];
        $data['electricity'] = $data['electricity_consumption'] * $number_data->electricity_price;
        $data['water_consumption'] = $data['water_meter_this_month'] - $data['water_meter_last_month'];
        $data['water'] = $data['water_consumption'] * $number_data->water_price;
        $data['total_money'] = intval($data['water'] + $data['electricity'] + $data['rental'] + $data['deposit']
             + $data['other_charges'] + $data['garbage_fee'] + $data['management']);
        $billing_data->save($data);
        $this->success('修改成功');
    }

    //到账
    public function account()
    {
        $id = $this->request->param('id/d', 0);
        if (!$billing_data = BillingModel::find($id)) {
            $this->error('记录不存在。');
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
            if($sum_data) {
                $sum_data->save([
                    'amount' => $sum_data->amount + $oldBill['total_money'],
                ]);
            } else {
                SumModel::create([
                    'house_property_id' => $oldBill->house_property_id,
                    'amount' => $oldBill->total_money,
                    'type' => TYPE_INCOME,
                    'accounting_date' => $accounting_month
                ]);
            }
            // 新增水电表记录
            if ($oldBill->electricity) {
                $electricity = MeterModel::where('house_property_id', $oldBill->house_property_id)
                    ->where('type', TYPE_ELECTRICITY)
                    ->whereFindInSet('house_number_id', $oldBill->house_number_id)
                    ->find();
                HydroelectricityModel::create([
                    'bill_meter_id' => $electricity->id,
                    'amount' => $oldBill->electricity,
                    'dosage' => $oldBill->electricity_consumption,
                    'type' => TYPE_ELECTRICITY,
                    'calculate_date' => date("Y-m-d", strtotime("-1 month", strtotime($oldBill->start_time)))
                ]);
            }
            if ($oldBill->water) {
                $water = MeterModel::where('house_property_id', $oldBill->house_property_id)
                    ->where('type', TYPE_WATER)
                    ->whereFindInSet('house_number_id', $oldBill->house_number_id)
                    ->find();
                HydroelectricityModel::create([
                    'bill_meter_id' => $water->id,
                    'amount' => $oldBill->water,
                    'dosage' => $oldBill->water_consumption,
                    'type' => TYPE_WATER,
                    'calculate_date' => date("Y-m-d", strtotime("-1 month", strtotime($oldBill->start_time)))
                ]);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            Db::rollback();
            // 回滚事务
            $this->error($e->getMessage());
        }
        if ($transFlag) {
            $this->success('操作成功');
        }
    }

    //收据页面-查询历史水电
    public function queryHistory()
    {
        $limit = 5;
        $number_id = $this->request->param('number_id/d', 0);
        $tenant_id = $this->request->param('tenant_id/d', 0);
        $conditions = array(
            ['a.house_number_id', '=', $number_id],
            ['a.tenant_id', '=', $tenant_id],
            // ['a.accounting_date', 'not null', ''],
            ['a.end_time', 'not null', '']
        );
        $billing_data = BillingModel::where($conditions)
        ->alias('a')
        ->join('HouseNumber b', 'b.id = a.house_number_id')
        ->join('HouseProperty c', 'c.id = a.house_property_id')
        ->field('a.*, b.name as number_name, c.name as property_name')
        ->order(['a.start_time' => 'desc'])
        ->limit($limit)
        ->select();
        foreach ($billing_data as $value) {
            $value['start_time'] = \substr($value['start_time'], 0, 10);
        }
        return $this->returnElement($billing_data, $limit);
    }

    //集中抄水电表
    public function centralized()
    {
        $type = $this->request->param('type/s');
        $house_property_id = $this->request->param('house_property_id/d', 0);
        $conditions = array(
            ['a.house_property_id', '=', $house_property_id],
            ['a.start_time', '< time', 'today+10 days'],
            ['a.accounting_date', 'null', ''],
            ['a.end_time', 'not null', ''],
        );
        if ($type == 'e') {
            array_push($conditions, ['a.electricity_meter_this_month', 'null', '']);
        } elseif ($type == 'w') {
            array_push($conditions, ['a.water_meter_this_month', 'null', '']);
        }
        $data = BillingModel::where($conditions)
        ->alias('a')
        ->join('HouseNumber b', 'b.house_property_id = a.house_property_id and b.id = a.house_number_id')
        ->field('a.*, b.name')
        ->order('b.name')
        ->select();
        return $this->returnElement($data);
    }

    //保存集中抄表
    public function saveCentralized()
    {
        $data = $this->request->post('data');
        $house_property_id = $this->request->post('house_property_id/d', 0);
        $type = $this->request->post('type/s', 0);
        foreach ($data as $key => $value) {
            if ($value) {
                if (!$billing = BillingModel::find($key)) {
                    $this->error('修改失败，记录不存在');
                }
                $number_data = NumberModel::where('house_property_id', $billing->house_property_id)
                ->where('id', $billing->house_number_id)
                ->find();
                $data = array();
                $data['meter_reading_time'] = date('Y-m-d', time());
                if ($type == 'e') {
                    $data['electricity_meter_this_month'] = $value;
                    $data['electricity_consumption'] = $value - $billing['electricity_meter_last_month'];
                    $data['electricity'] = $data['electricity_consumption'] * $number_data->electricity_price;
                    $data['total_money'] = intval($billing['water'] + $data['electricity'] + $billing['rental']
                        + $billing['deposit'] + $billing['other_charges'] + $billing['management'] + $billing['garbage_fee']);
                    $billing->save($data);
                } elseif ($type == 'w') {
                    $data['water_meter_this_month'] = $value;
                    $data['water_consumption'] = $value - $billing['water_meter_last_month'];
                    $data['water'] = $data['water_consumption'] * $number_data->water_price;
                    $data['total_money'] = intval($data['water'] + $billing['electricity'] + $billing['rental']
                        + $billing['deposit'] + $billing['other_charges'] + $billing['management'] + $billing['garbage_fee']);
                    $billing->save($data);
                }
            }
        }
        $this->success('修改成功');
    }

    // 借贷平衡
    public function balance()
    {
        $id = $this->request->param('id/d', 0);
        if (!$billing_data = BillingModel::find($id)) {
            $this->error('记录不存在。');
        }
        // 新增借贷账单
        $data_debit = [
            'house_property_id' => $billing_data['house_property_id'],
            'house_number_id' => $billing_data['house_number_id'],
            'start_time' =>  $billing_data['start_time'],
            'other_charges' => $billing_data['total_money'],
            'total_money' => $billing_data['total_money'],
            'note' => '借贷平衡',
        ];
        $data_crebit = [
            'house_property_id' => $billing_data['house_property_id'],
            'house_number_id' => $billing_data['house_number_id'],
            'start_time' =>  $billing_data['start_time'],
            'other_charges' => 0 - $billing_data['total_money'],
            'total_money' => 0 - $billing_data['total_money'],
            'note' => '借贷平衡',
            'accounting_date' => date('Y-m-d', time()),
        ];
        $transFlag = true;
        Db::startTrans();
        try {
            $number_data = NumberModel::find($billing_data->house_number_id);
            $billing_update['accounting_date'] = date('Y-m-d', time());

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


            // 新增下一个账单
            $new_billing = BillingModel::create($billing_insert);
            $number_update['payment_time'] = $billing_insert['start_time'];
            $number_update['receipt_number'] = $new_billing['id'];
            $number_update['lease'] = $number_data['lease'] + $number_data['lease_type'];
            // 更新房间信息
            $number_data->save($number_update);
            // 更新旧账单
            $billing_data->save($billing_update);
            // 新增借贷账单
            BillingModel::create($data_debit);
            BillingModel::create($data_crebit);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            Db::rollback();
            // 回滚事务
        }
        if ($transFlag) {
            $this->success('操作成功');
        } else {
            $this->error('111操作成功');
        }
    }
}
