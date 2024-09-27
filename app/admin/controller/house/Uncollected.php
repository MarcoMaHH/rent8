<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\HouseTenant as TenantModel;
use app\admin\model\HouseBilling as BillingModel;
use app\admin\model\BillSum as SumModel;
use app\admin\model\WeMeter as MeterModel;
use app\admin\model\WeDetail as WeDetailModel;
use app\common\house\Uncollected as UncollectedAction;
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
        ->order('a.meter_reading_time', 'asc')
        ->select();
        $data = [];
        foreach ($datas as $value) {
            if ($value['meter_reading_time']) {
                $value['meter_reading_time'] = \substr($value['meter_reading_time'], 0, 10);
                array_push($data, $value);
            }
        }
        return $this->returnResult($data);
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
            $value['total_money2'] = Property::convert_case_number($value['total_money']);
        }
        return $this->returnResult($datas, $count);
    }

    //抄表页面 保存-common
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
            'network' => $this->request->post('network/d', 0),
            'garbage_fee' => $this->request->post('garbage_fee/d', 0),
            'other_charges' => $this->request->post('other_charges/f', 0),
            'note' => $this->request->post('note/s', '', 'trim'),
        ];
        $result = UncollectedAction::save($id, $data);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    //到账-common
    public function account()
    {
        $id = $this->request->param('id/d', 0);
        $result = UncollectedAction::account($id, $this->auth->getLoginUser()['id']);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
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
        return $this->returnResult($billing_data, $limit);
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
            ['a.electricity_meter_last_month', 'not null', ''],
            ['a.water_meter_last_month', 'not null', ''],
        );
        if ($type == TYPE_ELECTRICITY) {
            array_push($conditions, ['a.electricity_meter_this_month', 'null', '']);
        } elseif ($type == TYPE_WATER) {
            array_push($conditions, ['a.water_meter_this_month', 'null', '']);
        }
        $data = BillingModel::where($conditions)
        ->alias('a')
        ->join('HouseNumber b', 'b.house_property_id = a.house_property_id and b.id = a.house_number_id')
        ->field('a.*, b.name')
        ->order('b.name')
        ->select();
        return $this->returnResult($data);
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
                    return $this->returnError('修改失败，记录不存在');
                }
                $number_data = NumberModel::where('house_property_id', $billing->house_property_id)
                ->where('id', $billing->house_number_id)
                ->find();
                $data = array();
                $data['meter_reading_time'] = date('Y-m-d', time());
                if ($type == TYPE_ELECTRICITY) {
                    $data['electricity_meter_this_month'] = $value;
                    $data['electricity_consumption'] = $value - $billing['electricity_meter_last_month'];
                    $data['electricity'] = $data['electricity_consumption'] * $number_data->electricity_price;
                    $data['total_money'] = round($billing['water'] + $data['electricity'] + $billing['rental']
                        + $billing['deposit'] + $billing['other_charges'] + $billing['management'] + $billing['network'] + $billing['garbage_fee'], 2);
                    $billing->save($data);
                } elseif ($type == TYPE_WATER) {
                    $data['water_meter_this_month'] = $value;
                    $data['water_consumption'] = $value - $billing['water_meter_last_month'];
                    $data['water'] = $data['water_consumption'] * $number_data->water_price;
                    $data['total_money'] = round($data['water'] + $billing['electricity'] + $billing['rental']
                        + $billing['deposit'] + $billing['other_charges'] + $billing['management'] + $billing['network'] + $billing['garbage_fee'], 2);
                    $billing->save($data);
                }
            }
        }
        return $this->returnSuccess('修改成功');
    }

    // 延期
    public function balance()
    {
        $id = $this->request->param('id/d', 0);
        if (!$billing_data = BillingModel::find($id)) {
            return $this->returnError('记录不存在。');
        }
        // 新增延期账单
        $data_debit = [
            'house_property_id' => $billing_data['house_property_id'],
            'house_number_id' => $billing_data['house_number_id'],
            'start_time' =>  $billing_data['start_time'],
            'other_charges' => $billing_data['total_money'],
            'total_money' => $billing_data['total_money'],
            'note' => '延期',
        ];
        $transFlag = true;
        Db::startTrans();
        try {
            $number_data = NumberModel::find($billing_data->house_number_id);
            $billing_update['accounting_date'] = date('Y-m-d', time());
            $billing_update['total_money'] = 0;
            $billing_update['note'] = '延期';

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
                'total_money' => ($number_data['rental'] + $number_data['management'] + $number_data['network'] + $number_data['garbage_fee']) * $number_data['lease_type'],
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
            // 新增延期账单
            BillingModel::create($data_debit);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            Db::rollback();
            // 回滚事务
        }
        if ($transFlag) {
            return $this->returnSuccess('操作成功');
        } else {
            return $this->returnError('操作失败');
        }
    }
}
