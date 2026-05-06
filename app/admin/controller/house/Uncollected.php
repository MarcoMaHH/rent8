<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseNumber as NumberModel;
use app\common\house\Uncollected as UncollectedAction;
use app\admin\model\HouseReceipt as ReceiptModel;
use app\admin\model\HouseFee as FeeModel;
use app\admin\library\Property;
use app\admin\library\Date;
use think\facade\Db;
use think\facade\View;

class Uncollected extends Common
{
    public function index()
    {
        return View::fetch('/house/uncollected/index');
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
        $datas = ReceiptModel::where($conditions)
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
    // table查询
    public function queryUncollected()
    {
        $order = $this->request->param('order/s', '', 'trim');
        $field = $this->request->param('field/s', '', 'trim');
        if (!$order) {
            $field = 'a.start_time, a.house_property_id, b.name';
            $order = 'asc';
        }
        $house_property_id = Property::getProperty();
        $meter_reading_time = $this->request->param('meter_reading_time/s', '', 'trim');
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id],
            ['a.start_time', '< time', 'today+10 days'],
            ['a.accounting_date', 'null', ''],
        );
        if ($meter_reading_time) {
            \array_push($conditions, ['a.meter_reading_time', '=', $meter_reading_time]);
        }
        $count = ReceiptModel::where($conditions)
            ->alias('a')
            ->count();
        $datas = ReceiptModel::where($conditions)
            ->alias('a')
            ->join('HouseNumber b', 'b.house_property_id = a.house_property_id and b.id = a.house_number_id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->field('a.*, b.name as number_name, c.name as property_name, c.name_show as property_name_show, b.electricity_price, b.water_price')
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
    //收据页面-查询历史水电
    public function queryHistory()
    {
        $limit = 5;
        $number_id = $this->request->param('number_id/d', 0);
        $conditions = array(
            ['a.house_number_id', '=', $number_id],
            // ['a.accounting_date', 'not null', ''],
            ['a.end_time', 'not null', '']
        );
        $billing_data = ReceiptModel::where($conditions)
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
    public function getColumns()
    {
        $fees = FeeModel::where([
            ['admin_user_id', '=', $this->auth->getLoginUser()['id']],
            ['column_name', '<>', '']
        ])->select()->toArray();
        return $this->returnResult($fees);
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

    //抄表页面
    public function save()
    {
        View::assign('id', $this->request->get('id/d', 0));
        View::assign('house_property_id', $this->request->get('house_property_id/d', 0));
        return View::fetch('/house/uncollected/save');
    }
    public function queryUncollectedEdit()
    {
        $id = $this->request->param('id/d', 0);
        $data = ReceiptModel::alias('a')
            ->leftJoin('HouseProperty b', 'a.house_property_id = b.id')
            ->leftJoin('HouseNumber c', 'a.house_number_id = c.id and c.house_property_id = b.id')
            ->where('a.id', $id)
            ->field('a.*, b.name as property_name, c.name as number_name')
            ->select()->toArray();
        foreach ($data as &$value) {
            if (empty($value['meter_reading_time'])) {
                $value['meter_reading_time'] = date('Y-m-d');
            }
        }
        unset($value);
        $countData = count($data);
        if ($countData == 0) {
            return $this->returnError('账单不存在');
        } else {
            return $this->returnResult($data[0], 1, $data[0]['property_name'] . ' - ' . $data[0]['number_name'] . ' 账单详情');
        }
    }
    public function runSave()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'house_property_id' => $this->request->post('house_property_id/d', 0),
            'house_number_id' => $this->request->post('house_number_id/d', 0),
            'start_time' => $this->request->post('start_time/s', '', 'trim'),
            'end_time' => $this->request->post('end_time/s', null, 'trim'),
            'electricity_meter_last_month' => $this->request->post('electricity_meter_last_month/d', 0),
            'water_meter_last_month' => $this->request->post('water_meter_last_month/d', 0),
            'meter_reading_time' => $this->request->post('meter_reading_time/s', '', 'trim'),
            'electricity_meter_this_month' => $this->request->post('electricity_meter_this_month/d', 0),
            'water_meter_this_month' => $this->request->post('water_meter_this_month/d', 0),
            'rental' => $this->request->post('rental/d', 0),
            'deposit' => $this->request->post('deposit/d', 0),
            'fees1' => $this->request->post('fees1/d', 0),
            'fees2' => $this->request->post('fees2/d', 0),
            'fees3' => $this->request->post('fees3/d', 0),
            'fees4' => $this->request->post('fees4/d', 0),
            'note' => $this->request->post('note/s', '', 'trim'),
        ];
        $result = UncollectedAction::save($id, $data, $this->auth->getLoginUser()['id']);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    //集中抄水电表-页面
    public function centralized()
    {
        return View::fetch('/house/uncollected/centralized');
    }
    // 查询
    public function queryCentralized()
    {
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id],
            ['a.start_time', '< time', 'today+10 days'],
            ['a.accounting_date', 'null', ''],
            ['a.end_time', 'not null', ''],
            ['a.meter_reading_time', 'null', ''],
            ['a.electricity_meter_last_month', 'not null', ''],
            ['a.water_meter_last_month', 'not null', ''],
        );
        $data = ReceiptModel::where($conditions)
            ->alias('a')
            ->join('HouseNumber b', 'b.house_property_id = a.house_property_id and b.id = a.house_number_id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->field('a.*, b.name as number_name, c.name as property_name')
            ->order('c.name, b.name')
            ->select()->toArray();
        return $this->returnResult($data);
    }
    //保存集中抄表
    public function saveCentralized()
    {
        $data = $this->request->post('data');
        foreach ($data as $value) {
            if (count($value) > 0) {
                if (!$recepit_data = ReceiptModel::find($value['id'])) {
                    return ['flag' => false, 'msg' => '修改失败，账单不存在'];
                }
                $number_data = NumberModel::where('house_receipt_id', $recepit_data->id)->find();
                $update_data = array();
                $update_data['meter_reading_time'] = date('Y-m-d', time());
                // 电
                $update_data['electricity_meter_this_month'] = $value['electricity'];
                $update_data['electricity_consumption'] = $value['electricity'] - $recepit_data['electricity_meter_last_month'];
                $update_data['electricity'] = $update_data['electricity_consumption'] * $number_data->electricity_price;
                // 水
                $update_data['water_meter_this_month'] = $value['water'];
                $update_data['water_consumption'] = $value['water'] - $recepit_data['water_meter_last_month'];
                $update_data['water'] = $update_data['water_consumption'] * $number_data->water_price;
                $update_data['total_money'] = intval($update_data['water'] + $update_data['electricity'] + $recepit_data['rental']
                    + $recepit_data['deposit'] + $recepit_data['fees1'] + $recepit_data['fees2'] + $recepit_data['fees3'] + $recepit_data['fees4']);
                $recepit_data->save($update_data);
            }
        }
        return $this->returnSuccess('保存成功');
    }

    // 延期
    public function balance()
    {
        $id = $this->request->param('id/d', 0);
        if (!$receipt_data = ReceiptModel::find($id)) {
            return $this->returnError('账单不存在');
        }
        $number_data = NumberModel::where('house_receipt_id', $receipt_data->id)->find();
        // 新增延期账单
        $data_debit = [
            'bill_type' => config('params.bill_types')['逾期账单'],
            'house_property_id' => $receipt_data['house_property_id'],
            'house_number_id' => $receipt_data['house_number_id'],
            'tenant_message_id' =>  $receipt_data['tenant_message_id'],
            'start_time' =>  $receipt_data['start_time'],
            'total_money' => $receipt_data['total_money'],
            'note' => '延期，总金额：' . $receipt_data['total_money'],
        ];
        $transFlag = true;
        Db::startTrans();
        try {
            // 重新计算日期
            $receipt_update['accounting_date'] = date('Y-m-d', time());
            $receipt_update['total_money'] = 0;
            $receipt_update['note'] = '延期，总金额：' . $receipt_data['total_money'];
            // 新增下一周期收据单
            $dates = Date::getLease($number_data->calculate_date, $number_data->cycle, $number_data->cycle_type, $number_data->lease);
            $nextDate = date('Y-m-d', strtotime("$receipt_data->end_time +1 day"));
            $receipt_insert = [
                'bill_type' => $receipt_data['bill_type'],
                'house_property_id' => $receipt_data['house_property_id'],
                'house_number_id' => $receipt_data['house_number_id'],
                'tenant_message_id' => $receipt_data['tenant_message_id'],
                'rental' => $number_data['rental'],
                'fees1' => $number_data['fees1'],
                'fees2' => $number_data['fees2'],
                'fees3' => $number_data['fees3'],
                'fees4' => $number_data['fees4'],
                'electricity_meter_last_month' => $receipt_data['electricity_meter_this_month'],
                'water_meter_last_month' => $receipt_data['water_meter_this_month'],
                'total_money' => $number_data['rental'] + $number_data['fees1']  + $number_data['fees2'] + $number_data['fees3'] + $number_data['fees4'],
            ];
            if ($dates[0] ===  $nextDate) {
                $receipt_insert['start_time'] = $dates[0];
                $receipt_insert['end_time'] = $dates[1];
                $number_update['lease'] = $number_data['lease'] + 1;
            } else {
                // 计算日期改变了，重新计算账单周期和起始时间
                $receipt_insert['start_time'] = $nextDate;
                $receipt_insert['end_time'] = Date::getLease($nextDate, $number_data->cycle, $number_data->cycle_type, 0)[1];
                $number_update['calculate_date'] = $nextDate;
                $number_update['lease'] = 1;
            }
            $new_receipt = ReceiptModel::create($receipt_insert);
            // 更新账单信息
            $number_update = [
                'payment_date' => $receipt_insert['start_time'],
                'house_receipt_id' => $new_receipt->id,
            ];
            $number_data->save($number_update);
            // 更新旧收据单
            $receipt_data->save($receipt_update);
            // 新增延期收据单
            ReceiptModel::create($data_debit);
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
