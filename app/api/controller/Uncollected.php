<?php

namespace app\api\controller;

use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\HouseReceipt as ReceiptModel;
use app\admin\model\AdminSubscribe as SubscribeModel;
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
        $datas = ReceiptModel::where($conditions)
            ->alias('a')
            ->leftJoin('HouseNumber b', 'b.house_property_id = a.house_property_id and b.id = a.house_number_id')
            ->leftJoin('HouseProperty c', 'c.id = a.house_property_id')
            ->leftJoin('AdminParameter d', "a.bill_type = d.value and d.name = 'bill_type'")
            ->field('a.*, b.name as number_name, c.name as property_name, d.label as bill_type_name, d.note as bill_type_note')
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
            $value['bill_name'] = $value['property_name']  . ' - ' . $value['number_name'];
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
        $datas = ReceiptModel::where($conditions)
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
        if (!$receipt_data = ReceiptModel::find($id)) {
            return $this->returnError('账单不存在');
        }
        $number_data = NumberModel::find($receipt_data->house_number_id);
        $property_data = PropertyModel::find($receipt_data->house_property_id);
        $data = [
            "receipt_id" => $property_data->name . '-' . $number_data->name,
            "start_time" => substr($receipt_data->start_time, 0, 10),
            "end_time" => $receipt_data->end_time ? substr($receipt_data->end_time, 0, 10) : '',
            "electricity_meter_this_month" => $receipt_data->electricity_meter_this_month,
            "electricity_meter_last_month" => $receipt_data->electricity_meter_last_month,
            "electricity_consumption" => $receipt_data->electricity_consumption,
            "electricity_price" => $number_data->electricity_price,
            "electricity" => $receipt_data->electricity,
            "meter_reading_time" => $receipt_data->meter_reading_time ? substr($receipt_data->meter_reading_time, 0, 10) : '',
            "water_meter_this_month" => $receipt_data->water_meter_this_month,
            "water_meter_last_month" => $receipt_data->water_meter_last_month,
            "water_consumption" => $receipt_data->water_consumption,
            "water_price" => $number_data->water_price,
            "water" => $receipt_data->water,
            "rental" => $receipt_data->rental,
            "deposit" => $receipt_data->deposit,
            "fees1" => $receipt_data->fees1,
            "fees2" => $receipt_data->fees2,
            "fees3" => $receipt_data->fees3,
            "fees4" => $receipt_data->fees4,
            "total_money" => $receipt_data->total_money,
            "note" => $receipt_data->note,
        ];
        return $this->returnWechat($data);
    }

    //抄表
    public function edit()
    {
        $id = $this->request->param('id/d', 0);
        $data = ReceiptModel::alias('a')
            ->leftJoin('HouseProperty b', 'a.house_property_id = b.id')
            ->leftJoin('HouseNumber c', 'a.house_number_id = c.id and c.house_property_id = b.id')
            ->where('a.id', $id)
            ->field('a.*, CONCAT(b.name, " - ", c.name) as receipt_name')
            ->find();
        if (!$data) {
            return $this->returnError('账单不存在');
        }
        if ($data['meter_reading_time']) {
            $data['meter_reading_time'] = \substr($data['meter_reading_time'], 0, 10);
        }
        if ($data['start_time']) {
            $data['start_time'] = \substr($data['start_time'], 0, 10);
        }
        if ($data['end_time']) {
            $data['end_time'] = \substr($data['end_time'], 0, 10);
        }
        $returnData = [
            "code" => 1,
            "data" => $data
        ];
        return \json($returnData);
    }

    //抄表页面 保存-common
    public function save()
    {
        // 获取原始 JSON 输入并解码
        $jsonData = $this->request->getInput();
        $postData = json_decode($jsonData, true);

        $id = $postData['id'] ?? 0;
        $data = [
            'house_property_id' => $postData['house_property_id'] ?? 0,
            'house_number_id' => $postData['house_number_id'] ?? 0,
            'tenant_message_id' => $postData['tenant_message_id'] ?? 0,
            'start_time' => $postData['start_time'] ?? '',
            'end_time' => $postData['end_time'] ?? null,
            'meter_reading_time' => $postData['meter_reading_time'] ?? '',
            'electricity_meter_this_month' => $postData['electricity_meter_this_month'] ?? 0,
            'water_meter_this_month' => $postData['water_meter_this_month'] ?? 0,
            'electricity_meter_last_month' => $postData['electricity_meter_last_month'] ?? 0,
            'water_meter_last_month' => $postData['water_meter_last_month'] ?? 0,
            'rental' => $postData['rental'] ?? 0,
            'deposit' => $postData['deposit'] ?? 0,
            'fees1' => $postData['fees1'] ?? 0,
            'fees2' => $postData['fees2'] ?? 0,
            'fees3' => $postData['fees3'] ?? 0,
            'fees4' => $postData['fees4'] ?? 0,
            'note' => $postData['note'] ?? '',
        ];
        $result = UncollectedAction::save($id, $data, $this->auth->getLoginUser()['id']);
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
        $data = ReceiptModel::where($conditions)
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

    // 微信小程序订阅消息
    public function subscribe()
    {
        // 获取原始 JSON 输入并解码
        $jsonData = $this->request->getInput();
        $postData = json_decode($jsonData, true);

        $id = $postData['id'] ?? 0;
        $saveData = [
            'house_property_id' => $postData['house_property_id'] ?? 0,
            'house_number_id' => $postData['house_number_id'] ?? 0,
            'tenant_message_id' => $postData['tenant_message_id'] ?? 0,
            'start_time' => $postData['start_time'] ?? '',
            'end_time' => $postData['end_time'] ?? null,
            'meter_reading_time' => $postData['meter_reading_time'] ?? '',
            'electricity_meter_this_month' => $postData['electricity_meter_this_month'] ?? 0,
            'water_meter_this_month' => $postData['water_meter_this_month'] ?? 0,
            'electricity_meter_last_month' => $postData['electricity_meter_last_month'] ?? 0,
            'water_meter_last_month' => $postData['water_meter_last_month'] ?? 0,
            'rental' => $postData['rental'] ?? 0,
            'deposit' => $postData['deposit'] ?? 0,
            'fees1' => $postData['fees1'] ?? 0,
            'fees2' => $postData['fees2'] ?? 0,
            'fees3' => $postData['fees3'] ?? 0,
            'fees4' => $postData['fees4'] ?? 0,
            'note' => $postData['note'] ?? '',
        ];

        $data = ReceiptModel::alias('a')
            ->leftJoin('HouseProperty b', 'a.house_property_id = b.id')
            ->leftJoin('HouseNumber c', 'a.house_number_id = c.id and c.house_property_id = b.id')
            ->where('a.id', $id)
            ->field('a.*, CONCAT(b.name, " - ", c.name) as receipt_name')
            ->find();
        if (!$data) {
            return $this->returnError('账单不存在');
        }
        $dateTime = new \DateTime($data['end_time']);
        $dateTime->modify('+1 day');

        // 获取用户openid
        $code = $postData['code'] ?? '';
        try {
            $ch = curl_init();
            // 构建包含查询字符串的URL
            $url = 'https://api.weixin.qq.com/sns/jscode2session?' . http_build_query([
                'appid' => env('WECHAT_APP_ID'),
                'secret' => env('WECHAT_APP_SECRET'),
                'js_code' => $code,
                'grant_type' => 'authorization_code'
            ]);

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);

            if ($response === false) {
                return $this->returnSuccess(curl_error($ch));
            }
            curl_close($ch);
            $result = json_decode($response, true);
            if (isset($result['openid'])) {
                $data = [
                    'thing' => $data['receipt_name'],
                    'time' => $dateTime->format('Y-m-d H:i:s'),
                    'amount' => $data['rental'],
                    'open_id' => $result['openid'],
                    'subscribe_type' => TYPE_RENT,
                ];
                SubscribeModel::create($data);

                $result = UncollectedAction::save($id, $saveData, $this->auth->getLoginUser()['id']);
                if ($result['flag']) {
                    return $this->returnSuccess($result['msg']);
                } else {
                    return $this->returnError($result['msg']);
                }
            } else {
                return $this->returnError('订阅失败');
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }
}
