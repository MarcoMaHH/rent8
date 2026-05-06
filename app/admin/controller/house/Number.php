<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\AdminUser as UserModel;
use app\admin\model\AdminParameter as ParameterModel;
use app\admin\model\HouseReceipt as ReceiptModel;
use app\common\house\Number as NumberAction;
use app\admin\library\Property;
use app\admin\library\Date;
use think\facade\Db;
use think\facade\View;

class Number extends Common
{
    public function index()
    {
        return View::fetch('/house/number/index');
    }
    public function queryNumber()
    {
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id],
        );
        $parameter = $this->request->param('parameter/s', '');
        if ($parameter) {
            $conditions[] = function ($query) use ($parameter) {
                $query->where('a.name', 'like', "%{$parameter}%")
                    ->whereOr('b.name', 'like', "%{$parameter}%");
            };
        }
        $numbers = NumberModel::where($conditions)
            ->alias('a')
            ->leftjoin('HouseProperty b', 'a.house_property_id = b.id')
            ->leftJoin('AdminParameter c', "a.cycle_type = c.value and c.name = 'ymd'")
            ->field('a.*,b.name as property_name, c.label as cycle_type_name')
            ->order('a.house_property_id, a.name')
            ->select();
        $currentDateTime = new \DateTime();
        foreach ($numbers as &$value) {
            if ($value['cycle']) {
                $value['cycle_name'] = $value['cycle'] . $value['cycle_type_name'];
            }
            if (!$value['checkin_date']) {
                $value['idle'] = Date::formatDays($currentDateTime->diff(new \DateTime($value['calculate_date']))->days);
            }
            if ($value['checkin_date']) {
                $value['checkin_date'] = \substr($value['checkin_date'], 0, 10);
            }
            if ($value['payment_date']) {
                $value['payment_date'] = \substr($value['payment_date'], 0, 10);
            }
        }
        return $this->returnResult($numbers);
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
    // 主页面-获取房间信息
    public function getNumberMessage()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id =  Property::getProperty();
        $user = UserModel::find($loginUser['id']);
        $number_count =  $user->houseNumber->where('house_property_id', 'in', $house_property_id)->count();
        $empty_count =  $user->houseNumber->where('checkin_date', 'NULL', '')->where('house_property_id', 'in', $house_property_id)->count();
        $occupancy = $number_count == 0 ? '0%' : round((($number_count - $empty_count) / $number_count) * 100) . '%';
        $number_info = [
            'occupancy' => $occupancy,
            'rented' => $number_count - $empty_count,
            'empty' => $empty_count,
        ];
        return $this->returnResult($number_info);
    }

    // 修改页面
    public function save()
    {
        $id = $this->request->get('id/d', 0);
        View::assign('id', $id);
        $number_data = NumberModel::find($id);
        View::assign('house_property_id', $number_data->house_property_id);
        return View::fetch('/house/number/save');
    }
    public function queryNumberEdit()
    {
        $id = $this->request->param('id/d', 0);
        $house_property_id = $this->request->param('house_property_id/d', 0);
        $ymd = ParameterModel::where('name', 'ymd')->where('label', '月')->select();
        $property = PropertyModel::where('id', $house_property_id)->find();
        if ($id) {
            $data = NumberModel::where('a.id', $id)
                ->alias('a')
                ->join('HouseProperty b', 'a.house_property_id = b.id')
                ->field('a.*, b.name as property_name')
                ->select()->toArray();
            if (count($data) == 0) {
                return $this->returnError('房间不存在');
            } else {
                return $this->returnResult($data[0], 1, $property->name . ' - 编辑房间');
            }
        } else {
            $data = [
                'house_property_id' => $house_property_id,
                'cycle' => 1,
                'cycle_type' => $ymd[0]['value'],
            ];
            return $this->returnResult($data, 1, $property->name . ' - 新增房间');
        }
    }
    public function runSave()
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

    // 批量新增房间号-页面
    public function increase()
    {
        View::assign('house_property_id', $this->request->get('house_property_id/d', 0));
        return View::fetch('/house/number/increase');
    }
    public function runIncrease()
    {
        $numbdrData = $this->request->post();

        // 开始事务
        $transFlag = true;
        Db::startTrans();
        try {
            foreach ($numbdrData as $item) {
                if (NumberModel::where('name', $item['name'])
                    ->where('house_property_id', $item['house_property_id'])
                    ->find()
                ) {
                    throw new \Exception($item['name'] . '房间名已存在，请勿重复添加。');
                }
                $item['calculate_date'] = date('Y-m-d');
                NumberModel::create($item);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            // 回滚事务
            Db::rollback();
            return $this->returnError($e->getMessage());
        }
        if ($transFlag) {
            return $this->returnSuccess('批量新增成功');
        }
    }

    // 入住-页面
    public function checkin()
    {
        View::assign('id', $this->request->get('id/d', 0));
        return View::fetch('/house/number/checkin');
    }
    public function queryCheckin()
    {
        $id = $this->request->param('id/d', 0);
        $number = NumberModel::find($id);
        $property = PropertyModel::find($number->house_property_id);
        $data = [
            'house_property_id' => $number->house_property_id,
            'house_number_id' => $id,
            'calculate_date' => date('Y-m-d'),
            'tenant_name' => '新租客' . rand(1000, 9999)
        ];
        return $this->returnResult($data, 1, $property->name . '-' . $number->name . ' 租客入住');
    }
    public function runCheckin()
    {
        $data = [
            'house_property_id' => $this->request->post('house_property_id/d', 0),
            'house_number_id' => $this->request->post('house_number_id/d', 0),
            'calculate_date' => $this->request->post('calculate_date/s', '', 'trim'),
            'water_meter' => $this->request->post('water_meter/d', 0),
            'electricity_meter' => $this->request->post('electricity_meter/d', 0),
            'name' => $this->request->post('tenant_name/s', '', 'trim'),
            'id_card_number' => $this->request->post('id_card_number/s', '', 'trim'),
            'native_place' => $this->request->post('native_place/s', '', 'trim'),
            'note' => $this->request->post('note/s', '', 'trim'),
            'phone' => $this->request->post('phone/s', '', 'trim'),
            'sex' => $this->request->post('sex/s', '', 'trim'),
            'work_units' => $this->request->post('work_units/s', '', 'trim'),
        ];
        $result = NumberAction::checkin($data, $this->auth->getLoginUser()['id']);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    // 退房页面
    public function checkout()
    {
        View::assign('id', $this->request->get('id/d', 0));
        return View::fetch('/house/number/checkout');
    }
    public function queryCheckout()
    {
        $id = $this->request->param('id/d', 0);
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
        return $this->returnResult($data, 1, $property->name . '-' . $number->name . ' 租客退房');
    }
    public function runCheckout()
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
            return $this->returnResult();
        } else {
            $number = NumberModel::where('house_property_id', 'in', $house_property_id)
                ->order('name')
                ->field('id as value,name as label')
                ->select()
                ->toArray();
            return $this->returnResult($number);
        }
    }
}
