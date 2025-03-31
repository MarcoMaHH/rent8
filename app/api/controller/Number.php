<?php

namespace app\api\controller;

use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseNumber as NumberModel;
use app\common\house\Number as NumberAction;
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
            \array_push($conditions, ['a.rent_mark', '=', $state]);
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
            ->join('HouseProperty b', 'a.house_property_id = b.id')
            ->where($conditions)
            ->field('a.*, b.name as property_name')
            ->order('a.name')
            ->select();
        $currentDateTime = new \DateTime();
        foreach ($numbers as $value) {
            if ($value['rent_mark'] === 'N' && $value['payment_time']) {
                $value['idle'] = Date::formatDays($currentDateTime->diff(new \DateTime($value['payment_time']))->days);
            }
        }
        return $this->returnWechat($numbers);
    }

    public function edit()
    {
        $id = $this->request->param('id/d', 0);
        if ($id) {
            if (!$data = NumberModel::find($id)) {
                return $this->returnError('房间不存在');
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
            'equipment' => $this->request->post('equipment/s', '', 'trim'),
            'ratio' => $this->request->post('ratio/f', 1),
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
            'house_number_id' => $this->request->post('house_number_id/d', 0),
            'checkin_time' => $this->request->post('checkin_time/s', '', 'trim'),
            // 'phone' => '',
            // 'id_card_number' => '',
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
