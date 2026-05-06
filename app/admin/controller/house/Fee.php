<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseFee as FeeModel;
use think\facade\Db;
use think\facade\View;

class Fee extends Common
{
    // 设置其他费用
    public function fees()
    {
        return View::fetch('/house/property/fees');
    }

    public function queryFee()
    {
        $loginUser = $this->auth->getLoginUser();
        $fees = FeeModel::where('admin_user_id', $loginUser['id'])->select()->toArray();
        if (count($fees) > 0) {
            return $this->returnResult($fees, 1);
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
            return $this->returnResult($data, 1);
        }
    }

    public function saveFee()
    {
        $feeData = $this->request->post();
        $loginUser = $this->auth->getLoginUser();

        // 开始事务
        $transFlag = true;
        Db::startTrans();
        try {
            // 删除原有数据
            FeeModel::where('admin_user_id', $loginUser['id'])->delete();
            foreach ($feeData as $item) {
                FeeModel::create($item);
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
