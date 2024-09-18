<?php

namespace app\api\controller;

use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\validate\HouseNumber as NumberValidate;
use app\admin\model\HouseTenant as TenantModel;
use app\admin\model\HouseBilling as BillingModel;
use app\admin\model\BillMeter as MeterModel;
use app\admin\library\Property;
use think\facade\Db;

class Number extends Common
{
    public function query()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $numbers = NumberModel::where('a.house_property_id', $house_property_id)
        ->alias('a')
        ->join('HouseProperty b', 'a.house_property_id = b.id')
        ->field('a.*,b.name as property_name')
        ->order('a.name')
        ->select();
        $today = date("Y-m-d");
        return $this->returnWechat($numbers, 0, $today);
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
            'garbage_fee' => $this->request->post('garbage_fee/d', 0),
            'daily_rent' => $this->request->post('daily_rent/d', 0),
            'water_price' => $this->request->post('water_price/f', 0.0),
            'electricity_price' => $this->request->post('electricity_price/f', 0.0),
        ];
        $validate = new NumberValidate();
        if ($id) {
            if (!$validate->scene('update')->check($data)) {
                return $this->returnError('修改失败，' . $validate->getError() . '。');
            }
            if (!$permission = NumberModel::find($id)) {
                return $this->returnError('修改失败，记录不存在');
            }
            $permission->save($data);
            return $this->returnSuccess('修改成功');
        }
        if (NumberModel::where('name', $data['name'])->where('house_property_id', $data['house_property_id'])->find()) {
            return $this->returnError('房间名已存在');
        }
        NumberModel::create($data);
        return $this->returnSuccess('添加成功');
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
        // 租客资料
        $house_number_id = $this->request->post('house_number_id/d', 0);
        $checkin_time = $this->request->post('checkin_time/s', '', 'trim');
        $data = [
            'house_property_id' => $this->request->post('house_property_id/d', 0),
            'house_number_id' => $house_number_id,
            'name' => $this->request->post('name/s', '', 'trim'),
            'sex' => $this->request->post('sex/s', '', 'trim'),
            'checkin_time' => $checkin_time,
            'phone' => $this->request->post('phone/d', ''),
            'id_card_number' => $this->request->post('id_card_number/d', ''),
            'native_place' => $this->request->post('native_place/s', '', 'trim'),
            'work_units' => $this->request->post('work_units/s', '', 'trim'),
        ];
        if (!$number_data = NumberModel::find($house_number_id)) {
            $this->returnError('修改失败，记录不存在');
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
            BillingModel::where('house_property_id', $this->request->post('house_property_id/d', 0))
            ->where('house_number_id', $house_number_id)
            ->delete();
            //insert账单资料
            $billing_data = [
                'house_property_id' => $data['house_property_id'],
                'house_number_id' => $data['house_number_id'],
                'start_time' => $checkin_time,
                'end_time' => date('Y-m-d', strtotime("$checkin_time +$lease_type month -1 day")),
                'tenant_id' => $tenant->id,
                'rental' => $number_data['rental'] * $lease_type,
                'deposit' => $number_data['deposit'],
                'management' => $number_data['management'] * $lease_type,
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
            $this->returnError($e->getMessage());
        }
        if ($transFlag) {
            return $this->returnSuccess('添加租客成功');
        } else {
            $this->returnError('系统出错了');
        }
    }

    //退房
    public function checkout()
    {
        $number_id = $this->request->param('id/d', 0);
        $leave_time = $this->request->param('leave_time/s', date('Y-m-d'), 'trim');
        if (!$number_data = NumberModel::find($number_id)) {
            $this->returnError('修改失败，记录不存在');
        }
        $number_update = [
            'rent_mark' => 'N',
            'tenant_id' => '',
            'checkin_time' => null,
            // 'payment_time' => date('Y-m-d'),
            'lease' => 0,
        ];
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
            'garbage_fee' => 0,
            'note' => $note,
        ];
        $transFlag = true;
        Db::startTrans();
        try {
            $number_data->save($number_update);
            TenantModel::where('house_property_id', $number_data->house_property_id)
            ->where('house_number_id', $number_id)
            ->where('leave_time', 'null')
            ->data(['leave_time' => $leave_time, 'mark' => 'Y'])
            ->update();
            $billing_data->save($billing_update);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            // 回滚事务
            Db::rollback();
        }
        if ($transFlag) {
            return $this->returnSuccess('退房成功');
        }
    }
}
