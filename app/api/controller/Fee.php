<?php

namespace app\api\controller;

use app\admin\model\HouseFee as FeeModel;
use app\admin\model\HouseReceipt as ReceiptModel;
use app\admin\model\HouseProperty as PropertyModel;
use think\facade\Db;

class Fee extends Common
{
    protected $checkLoginExclude = ['queryFee2'];
    public function queryFee()
    {
        $loginUser = $this->auth->getLoginUser();
        $fees = FeeModel::where('admin_user_id', $loginUser['id'])->select()->toArray();
        if (count($fees) > 0) {
            return $this->returnWechat($fees, 1);
        } else {
            $data = [
                [
                    'admin_user_id' => $loginUser['id'],
                    'column_id' => 'fees1',
                    'column_name' => '',
                ],
                [
                    'admin_user_id' => $loginUser['id'],
                    'column_id' => 'fees2',
                    'column_name' => '',
                ],
                [
                    'admin_user_id' => $loginUser['id'],
                    'column_id' => 'fees3',
                    'column_name' => '',
                ],
                [
                    'admin_user_id' => $loginUser['id'],
                    'column_id' => 'fees4',
                    'column_name' => '',
                ],
            ];
            return $this->returnWechat($data, 1);
        }
    }

    // 根据收据id查询费用
    public function queryFee2()
    {
        $id = $this->request->param('id/d', 0);
        if (!$receipt_data = ReceiptModel::find($id)) {
            return $this->returnError('账单不存在');
        }
        if (!$property_data = PropertyModel::find($receipt_data['house_property_id'])) {
            return $this->returnError('房产不存在');
        }
        $fees = FeeModel::where('admin_user_id', $property_data['admin_user_id'])->select()->toArray();
        return $this->returnWechat($fees, 1);
    }

    public function saveFee()
    {
        $postdata = file_get_contents('php://input');
        // $postdata = $this->request->post();
        $feeData = json_decode($postdata, true);
        $loginUser = $this->auth->getLoginUser();
        // 开始事务
        $transFlag = true;
        Db::startTrans();
        try {
            // 删除原有数据
            FeeModel::where('admin_user_id', $loginUser['id'])->delete();
            foreach ($feeData as $item) {
                FeeModel::create([
                    'admin_user_id' => $item['admin_user_id'],
                    'column_id' => $item['column_id'],
                    'column_name' => $item['column_name'],
                ]);
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
            return $this->returnSuccess('保存成功');
        }
    }
}
