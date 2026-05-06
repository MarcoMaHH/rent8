<?php

namespace app\api\controller;

use app\api\library\Property;
use app\admin\model\TenantMaintain as MaintainModel;
use app\admin\model\HouseReceipt as ReceiptModel;
use app\common\house\Maintain as MaintainAction;

class Maintain extends Common
{
    protected $checkLoginExclude = ['save'];

    public function queryMaintain()
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
        $count = MaintainModel::alias('a')
            ->leftJoin('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->leftJoin('HouseProperty c', 'c.id = a.house_property_id')
            ->where($conditions)
            ->count();
        $maintain = MaintainModel::alias('a')
            ->leftJoin('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->leftJoin('HouseProperty c', 'c.id = a.house_property_id')
            ->field('a.*, b.name as number_name, c.name as property_name')
            ->where($conditions)
            ->order('a.status')
            ->select();
        foreach ($maintain as $value) {
            if ($value['create_time']) {
                $value['maintain_date'] = \substr($value['create_time'], 0, 10);
            }
            $value['maintain_name'] = $value['property_name']  . ' - ' . $value['number_name'];
        }
        return $this->returnWechat($maintain, $count);
    }
    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        $model = MaintainModel::find($id);
        if (!$model) {
            return $this->returnError('记录不存在');
        }
        $model->delete();
        return $this->returnSuccess('删除成功');
    }

    public function edit()
    {
        $id = $this->request->param('id/d', 0);
        $data = null; // 初始化$data变量
        if ($id) {
            $data = MaintainModel::alias('a')
                ->leftJoin('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
                ->leftJoin('HouseProperty c', 'c.id = a.house_property_id')
                ->field('a.*,b.name as number_name, c.name as property_name')
                ->find($id);
            if (!$data) {
                return $this->returnError('维修单不存在');
            }
        } else {
            return $this->returnError('维修单不存在');
        }

        $returnData = [
            "code" => 1,
            "data" => $data
        ];
        return \json($returnData);
    }

    public function handle()
    {
        $id = $this->request->post('id/d', 0);
        $total_money = $this->request->post('total_money/d', 0);
        $result = MaintainAction::handle($id, $total_money, $this->auth->getLoginUser()['id']);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    public function save()
    {
        $receiptId = $this->request->post('receiptId/d', 0);
        if (!$receiptArr = ReceiptModel::find($receiptId)) {
            return $this->returnError('账单不存在。');
        }
        $data = [
            'house_property_id' => $receiptArr->house_property_id,
            'house_number_id' => $receiptArr->house_number_id,
            'tenant_message_id' => $receiptArr->tenant_message_id,
            'status' => 'N',
            'description' => $this->request->post('textarea/s', '', 'trim'),
        ];
        MaintainModel::create($data);
        return $this->returnSuccess('保存成功');
    }
}
