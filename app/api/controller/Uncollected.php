<?php

namespace app\api\controller;

use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\HouseBilling as BillingModel;
use app\common\house\Uncollected as UncollectedAction;
use app\api\library\Property;

class Uncollected extends Common
{
    protected $checkLoginExclude = ['report'];
    //主页面 table查询
    public function queryUncollected()
    {
        $field = 'a.start_time';
        $order = 'asc';
        $house_property_id = Property::getProperty();
        $meter_reading_time = $this->request->param('meter_reading_time/s', '', 'trim');
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id],
            ['a.start_time', '< time', 'today+5 days'],
            ['a.accounting_date', 'null', ''],
        );
        if ($meter_reading_time) {
            \array_push($conditions, ['a.meter_reading_time', '=', $meter_reading_time]);
            $field = 'b.name';
        }
        $parameter = $this->request->param('parameter/s', '');
        if ($parameter) {
            $conditions[] = function ($query) use ($parameter) {
                $query->where('b.name', 'like', "%{$parameter}%")
                        ->whereOr('c.name', 'like', "%{$parameter}%");
            };
        }
        $datas = BillingModel::where($conditions)
        ->alias('a')
        ->join('HouseNumber b', 'b.house_property_id = a.house_property_id and b.id = a.house_number_id')
        ->join('HouseProperty c', 'c.id = a.house_property_id')
        ->field('a.*, b.name, c.name as property_name')
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
        return $this->returnWechat($datas);
    }

    // 抄表日期选项
    public function queryReadingTime()
    {
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id],
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
        return $this->returnWechat($datas);
    }

    // 生成收据
    public function report()
    {
        $id = $this->request->param('id/d', 0);
        if (!$billing_data = BillingModel::find($id)) {
            return $this->returnError('账单不存在');
        }
        $number_data = NumberModel::find($billing_data->house_number_id);
        $property_data = PropertyModel::find($billing_data->house_property_id);
        $data = [
            "number_id" => $property_data->name . '-' . $number_data->name,
            "start_time" => substr($billing_data->start_time, 0, 10),
            "end_time" => $billing_data->end_time ? substr($billing_data->end_time, 0, 10) : '',
            "electricity_meter_this_month" => $billing_data->electricity_meter_this_month,
            "electricity_meter_last_month" => $billing_data->electricity_meter_last_month,
            "electricity_consumption" => $billing_data->electricity_consumption,
            "electricity_price" => $number_data->electricity_price,
            "electricity" => $billing_data->electricity,
            "meter_reading_time" => $billing_data->meter_reading_time ? substr($billing_data->meter_reading_time, 0, 10) : '',
            "water_meter_this_month" => $billing_data->water_meter_this_month,
            "water_meter_last_month" => $billing_data->water_meter_last_month,
            "water_consumption" => $billing_data->water_consumption,
            "water_price" => $number_data->water_price,
            "water" => $billing_data->water,
            "rental" => $billing_data->rental,
            "deposit" => $billing_data->deposit,
            "garbage_fee" => $billing_data->garbage_fee,
            "management" => $billing_data->management,
            "network" => $billing_data->network,
            "other_charges" => $billing_data->other_charges,
            "total_money" => $billing_data->total_money,
            "note" => $billing_data->note,
        ];
        return $this->returnWechat($data);
    }

    //抄表
    public function edit()
    {
        $id = $this->request->param('id/d', 0);
        if (!$data = BillingModel::find($id)) {
            return $this->returnError('记录不存在。');
        }
        $number_data = NumberModel::where('house_property_id', $data->house_property_id)
            ->where('id', $data->house_number_id)
            ->find();
        $data['house_number_name'] = $number_data->name;
        $property_name = PropertyModel::find($data->house_property_id);
        $data['house_property_name'] = $property_name->name;
        $returnData = [
            "code" => 1,
            "data" => $data
        ];
        return \json($returnData);
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

    //集中抄水电表
    public function centralized()
    {
        $type = $this->request->param('type/s');
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id],
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
        ->join('HouseProperty c', 'c.id = a.house_property_id')
        ->field('a.*, b.name as number_name, c.name as property_name')
        ->order('b.name')
        ->select();

        $returnData = [
            "code" => 1,
            "data" => $data
        ];
        return \json($returnData);
    }

    //保存集中抄表
    public function saveCentralized()
    {
        $data = json_decode($this->request->post('data'));
        $type = $this->request->post('type/s', '');
        $arr = [];
        if (is_array($data)) {
            foreach ($data as $object) {
                if (is_object($object) && isset($object->id) && isset($object->value)) {
                    $arr[] = ['id' => $object->id, 'value' => $object->value];
                }
            }
        }
        $result = UncollectedAction::saveCentralized($arr, $type);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }
}
