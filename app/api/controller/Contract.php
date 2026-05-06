<?php

namespace app\api\controller;

use app\api\library\Property;
use app\admin\model\TenantContract as ContractModel;
use app\admin\model\AdminSubscribe as SubscribeModel;
use app\common\house\Contract as ContractAction;

class Contract extends Common
{
    public function queryContract()
    {
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id],
        );
        $parameter = $this->request->param('parameter/s', '');
        if ($parameter) {
            $conditions[] = function ($query) use ($parameter) {
                $query->where('b.name', 'like', "%{$parameter}%")
                    ->whereOr('c.name', 'like', "%{$parameter}%");
            };
        }
        $count = ContractModel::alias('a')
            ->leftJoin('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->leftJoin('HouseProperty c', 'c.id = a.house_property_id')
            ->where($conditions)
            ->count();
        $contract = ContractModel::alias('a')
            ->leftJoin('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->leftJoin('HouseProperty c', 'c.id = a.house_property_id')
            ->field('a.*, b.name as number_name, c.name as property_name')
            ->where($conditions)
            ->orderRaw('a.type DESC, CASE WHEN a.end_date IS NULL THEN 1 ELSE 0 END, a.end_date ASC, a.house_property_id')
            ->select();
        foreach ($contract as $value) {
            if ($value['start_date']) {
                $value['start_date'] = \substr($value['start_date'], 0, 10);
            }
            if ($value['end_date']) {
                if ($value['type'] == 'Y') {
                    $value['remaining'] = \floor((\strtotime($value['end_date']) - \strtotime('today midnight')) / 86400);
                }
                $value['end_date'] = \substr($value['end_date'], 0, 10);
            }
            $value['bill_name'] = $value['property_name']  . ' - ' . $value['number_name'];
        }
        return $this->returnWechat($contract, $count);
    }

    public function edit()
    {
        $id = $this->request->param('id/d', 0);
        $data = null; // 初始化$data变量
        if ($id) {
            $data = ContractModel::alias('a')
                ->leftJoin('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
                ->leftJoin('HouseProperty c', 'c.id = a.house_property_id')
                ->field('a.*,b.name as number_name, c.name as property_name')
                ->find($id);
            if (!$data) {
                return $this->returnError('合同不存在');
            }
            if ($data->start_date) {
                $data->start_date = \substr($data->start_date, 0, 10);
            }
            if ($data->end_date) {
                $data->end_date = \substr($data->end_date, 0, 10);
            }
        } else {
            return $this->returnError('缺少合同ID');
        }

        $returnData = [
            "code" => 1,
            "data" => $data
        ];
        return \json($returnData);
    }

    public function save()
    {
        $data = [
            'id' => $this->request->post('id/d', 0),
            'house_property_id' => $this->request->post('house_property_id/s', '', 'trim'),
            'house_number_id' => $this->request->post('house_number_id/s', '', 'trim'),
            'start_date' => $this->request->post('start_date/s', '', 'trim'),
            'end_date' => $this->request->post('end_date/s', '', 'trim'),
        ];
        $result = ContractAction::save($data);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    public function subscribe()
    {
        $id = $this->request->param('id/d', 0);
        $time = $this->request->param('time/s', '', 'trim');
        $data = ContractModel::alias('a')
            ->leftJoin('HouseProperty b', 'a.house_property_id = b.id')
            ->leftJoin('HouseNumber c', 'a.house_number_id = c.id and c.house_property_id = b.id')
            ->where('a.id', $id)
            ->field('a.*, CONCAT(b.name, " - ", c.name) as contract_name')
            ->find();
        if (!$data) {
            return $this->returnError('合同不存在');
        }

        // 获取用户openid
        $code = $this->request->post('code/s', '', 'trim');
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
                $subscribeData = [
                    'thing' => $data['contract_name'],
                    'time' => $time,
                    'open_id' => $result['openid'],
                    'subscribe_type' => TYPE_RENEWAL,
                ];
                SubscribeModel::create($subscribeData);

                return $this->returnSuccess('订阅成功');
            } else {
                return $this->returnError('订阅失败');
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }
}
