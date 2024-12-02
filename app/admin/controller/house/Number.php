<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\AdminUser as UserModel;
use app\common\house\Number as NumberAction;
use app\admin\library\Property;
use app\admin\library\Date;
use think\facade\Db;
use think\facade\View;

class Number extends Common
{
    public function index()
    {
        return View::fetch();
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
            ->join('HouseProperty b', 'a.house_property_id = b.id')
            ->field('a.*,b.name as property_name')
            ->order('a.house_property_id, a.name')
            ->select();
        $currentDateTime = new \DateTime();
        foreach ($numbers as &$value) {
            if ($value['lease']) {
                $value['rent_date'] = Date::getLease($value['checkin_time'], $value['lease'] - $value['lease_type'])[0];
            }
            if ($value['checkin_time']) {
                $value['checkin_time'] = \substr($value['checkin_time'], 0, 10);
            }
            if ($value['rent_mark'] === 'N' && $value['payment_time']) {
                $value['idle'] = Date::formatDays($currentDateTime->diff(new \DateTime($value['payment_time']))->days);
            }
        }
        return $this->returnResult($numbers);
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

    // 批量保存
    public function saveMore()
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
                    throw new \Exception('该房间已存在，请勿重复添加');
                }
                $item['payment_time'] = date('Y-m-d');
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
            return $this->returnSuccess('新建成功');
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

        if (!$number = NumberModel::find($id)) {
            return $this->returnError('删除失败,房间不存在。');
        }
    }

    //新租
    public function checkin()
    {
        $data = [
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

    // 房间管理页面-获取房间信息
    public function getNumberMessage()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id =  Property::getProperty();
        $user = UserModel::find($loginUser['id']);
        $number_count =  $user->houseNumber->where('house_property_id', 'in', $house_property_id)->count();
        $empty_count =  $user->houseNumber->where('rent_mark', 'N')->where('house_property_id', 'in', $house_property_id)->count();
        $occupancy = $number_count == 0 ? '0%' : round((($number_count - $empty_count) / $number_count) * 100) . '%';
        $number_info = [
            'occupancy' => $occupancy,
            'rented' => $number_count - $empty_count,
            'empty' => $empty_count,
        ];
        return $this->returnResult($number_info);
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
