<?php

namespace app\api\controller;

use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseNumber as NumberModel;
use app\common\house\Number as NumberAction;
use app\admin\model\HouseReceipt as ReceiptModel;
use app\api\library\Property;
use app\admin\library\Date;

class Number extends Common
{
    public function queryNumber()
    {
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id]
        );
        $state = $this->request->param('state/s', '');
        if ($state) {
            if ($state === 'N') {
                \array_push($conditions, ['a.checkin_date', 'NULL', '']);
            } else if ($state === 'Y') {
                \array_push($conditions, ['a.checkin_date', 'NOT NULL', '']);
            }
        }
        $parameter = $this->request->param('parameter/s', '');
        if ($parameter) {
            $conditions[] = function ($query) use ($parameter) {
                $query->where('a.name', 'like', "%{$parameter}%")
                    ->whereOr('b.name', 'like', "%{$parameter}%");
            };
        }
        $house_property_id = Property::getProperty();
        $numbers = NumberModel::alias('a')
            ->leftjoin('HouseProperty b', 'a.house_property_id = b.id')
            ->where($conditions)
            ->field('a.*, b.name as property_name')
            ->order('a.name')
            ->select();
        $currentDateTime = new \DateTime();
        foreach ($numbers as &$value) {
            if ($value['checkin_date'] == null && $value['calculate_date']) {
                $value['idle'] = Date::formatDays($currentDateTime->diff(new \DateTime($value['calculate_date']))->days);
            }
        }
        return $this->returnWechat($numbers);
    }

    public function edit()
    {
        $id = $this->request->param('id/d', 0);
        if (!$data = NumberModel::find($id)) {
            return $this->returnError('房间不存在');
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
            'cycle' => $this->request->post('cycle/d', 1),
            'cycle_type' => $this->request->post('cycle_type/s', 'month', 'trim'),
            'rental' => $this->request->post('rental/d', 0),
            'deposit' => $this->request->post('deposit/d', 0),
            'daily_rent' => $this->request->post('daily_rent/d', 0),
            'equipment' => $this->request->post('equipment/s', '', 'trim'),
            'water_price' => $this->request->post('water_price/f', 0),
            'electricity_price' => $this->request->post('electricity_price/f', 0),
            'fees1' => $this->request->post('fees1/d', 0),
            'fees2' => $this->request->post('fees2/d', 0),
            'fees3' => $this->request->post('fees3/d', 0),
            'fees4' => $this->request->post('fees4/d', 0),
        ];
        $result = NumberAction::save($id, $data);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    //checkin
    public function queryCheckin()
    {
        $id = $this->request->param('id/d', 0);
        if (!$number = NumberModel::find($id)) {
            return $this->returnError('房间不存在');
        }
        $number = NumberModel::find($id);
        $property = PropertyModel::find($number->house_property_id);
        $data = [
            'house_property_id' => $number->house_property_id,
            'house_number_id' => $id,
            'calculate_date' => date('Y-m-d'),
            'tenant_name' => '新租客' . rand(1000, 9999)
        ];
        $returnData = [
            "code" => 1,
            "data" => $data,
            "msg" => $property->name . '-' . $number->name
        ];
        return \json($returnData);
    }
    public function checkin()
    {
        $data = [
            'house_property_id' => $this->request->post('house_property_id/d', 0),
            'house_number_id' => $this->request->post('house_number_id/d', 0),
            'calculate_date' => $this->request->post('calculate_date/s', '', 'trim'),
            'water_meter' => $this->request->post('water_meter/d', 0),
            'electricity_meter' => $this->request->post('electricity_meter/d', 0),
            'name' => $this->request->post('tenant_name/s', '', 'trim'),
        ];
        $result = NumberAction::checkin($data, $this->auth->getLoginUser()['id']);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    // 退房页面
    public function queryCheckout()
    {
        $id = $this->request->param('id/d', 0);
        if (!$number = NumberModel::find($id)) {
            return $this->returnError('房间不存在');
        }
        $number = NumberModel::find($id);
        $property = PropertyModel::find($number->house_property_id);
        $receipt = ReceiptModel::find($number->house_receipt_id);
        $data = [
            'house_property_id' => $number->house_property_id,
            'house_number_id' => $id,
            'tenant_message_id' => $number->tenant_message_id,
            'house_receipt_id' => $number->house_receipt_id,
            'calculate_date' => date('Y-m-d'),
            'water_meter_last_month' => $receipt->water_meter_last_month,
            'electricity_meter_last_month' => $receipt->electricity_meter_last_month,
        ];
        $returnData = [
            "code" => 1,
            "data" => $data,
            "msg" => $property->name . '-' . $number->name,
        ];
        return \json($returnData);
    }
    public function checkout()
    {
        $data = [
            'house_property_id' => $this->request->post('house_property_id/d', 0),
            'house_number_id' => $this->request->post('house_number_id/d', 0),
            'tenant_message_id' => $this->request->post('tenant_message_id/d', 0),
            'house_receipt_id' => $this->request->post('house_receipt_id/d', 0),
            'calculate_date' => $this->request->post('calculate_date/s', '', 'trim'),
            'water_meter' => $this->request->post('water_meter/d', 0),
            'electricity_meter' => $this->request->post('electricity_meter/d', 0),
            'note' => $this->request->post('note/s', '', 'trim'),
        ];
        $result = NumberAction::checkout($data);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }


    //其他页面查询numberId-全部房产时，不可用
    public function queryNumberId()
    {
        $house_property_id = Property::getProperty();
        if (count($house_property_id) > 1) {
            return $this->returnWechat();
        } else {
            $number = NumberModel::where('house_property_id', 'in', $house_property_id)
                ->order('name')
                ->field('id as value,name as label')
                ->select()
                ->toArray();
            return $this->returnWechat($number);
        }
    }

    public function delete()
    {
        $id = $this->request->param('id/d', null);
        $result = NumberAction::delete($id);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }
}
