<?php

namespace app\api\controller;

use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\HouseTenant as TenantModel;
use app\admin\model\HouseBilling as BillingModel;
use app\common\house\Number as NumberAction;
use app\admin\library\Property;

class Number extends Common
{
    public function queryNumber()
    {
        $house_property_id = Property::getProperty();
        $numbers = NumberModel::where('a.house_property_id', 'in', $house_property_id)
        ->alias('a')
        ->join('HouseProperty b', 'a.house_property_id = b.id')
        ->field('a.*,b.name as property_name')
        ->order('a.name')
        ->select();
        return $this->returnWechat($numbers);
    }

    public function edit()
    {
        $id = $this->request->param('id/d', 0);
        if ($id) {
            if (!$data = NumberModel::find($id)) {
                return $this->returnError('记录不存在。');
            }
        }
        $property_name = PropertyModel::find($data->house_property_id);
        $data['house_property_name'] = $property_name->name;
        $returnData = [
            "code" => 1,
            "data" => $data
        ];
        return \json($returnData);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'house_property_id' => $this->request->post('house_property_id/d', 0),
            'name' => $this->request->post('name/s', '', 'trim'),
            'rental' => $this->request->post('rental/d', 0),
            'deposit' => $this->request->post('deposit/d', 0),
            'lease_type' => $this->request->post('lease_type/d', 0),
            'management' => $this->request->post('management/d', 0),
            'network' => $this->request->post('network/d', 0),
            'garbage_fee' => $this->request->post('garbage_fee/d', 0),
            'daily_rent' => $this->request->post('daily_rent/d', 0),
            'water_price' => $this->request->post('water_price/f', 0.0),
            'electricity_price' => $this->request->post('electricity_price/f', 0.0),
        ];
        $result = NumberAction::save($id, $data);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    //新租页面
    public function rental()
    {
        $house_number_id = $this->request->param('id/d', 0);
        $house_property_id = $this->request->param('house_property_id/d', 0);
        $data = [
                'house_property_id' => $house_property_id,
                'house_number_id' => $house_number_id,
                'name' => '',
                'sex' => '',
                'phone' => '',
                'id_card_number' => '',
                'native_place' => '',
                'work_units' => '',
                'checkin_time' => date("Y-m-d"),
            ];
        if (!$number_name = NumberModel::find($house_number_id)) {
            $this->returnError('房间不存在。');
        }
        $data['house_number_name'] = $number_name['name'];
        if (!$property_name = PropertyModel::find($house_property_id)) {
            $this->returnError('房产不存在。');
        }
        $data['house_property_name'] = $property_name['name'];
        $returnData = [
            "code" => 1,
            "data" => $data,
        ];
        return \json($returnData);
    }

    //新租
    public function checkin()
    {
        $data = [
            'house_property_id' => $this->request->post('house_property_id/d', 0),
            'house_number_id' => $this->request->post('house_number_id/d', 0),
            'checkin_time' => $this->request->post('checkin_time/s', '', 'trim'),
        ];
        $result = NumberAction::checkin($data);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    //退房
    public function checkout()
    {
        $number_id = $this->request->param('id/d', 0);
        $leave_time = $this->request->param('leave_time/s', date('Y-m-d'), 'trim');
        $result = NumberAction::checkout($number_id, $leave_time);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }
}
