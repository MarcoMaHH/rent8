<?php

namespace app\api\controller;

use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseBilling as BillingModel;
use app\admin\model\HouseContract as ContractModel;
use app\admin\model\BillSum as SumModel;

class Index extends Common
{
    public function queryIndex()
    {
        $loginUser = $this->auth->getLoginUser();
        $property = PropertyModel::where('admin_user_id', $loginUser['id'])->select()->toArray();
        $result = array_map(function ($item) {
            return $item['id'];
        }, $property);
        $accounting_month = date('Y-m');
        $income = SumModel::where('house_property_id', 'in', $result)
            ->where('type', TYPE_INCOME)
            ->where('accounting_date', $accounting_month)
            ->sum('amount');
        $spending = SumModel::where('house_property_id', 'in', $result)
            ->where('type', TYPE_EXPENDITURE)
            ->where('accounting_date', $accounting_month)
            ->sum('amount');
        $threeDaysAgo = date('Y-m-d H:i:s', strtotime('+3 days'));
        $bill = BillingModel::where('house_property_id', 'in', $result)
            ->where('accounting_date', null)
            ->where('start_time', '<', $threeDaysAgo)
            ->count();
        $contract = ContractModel::where('house_property_id', 'in', $result)
            ->where('end_date', '<', $threeDaysAgo)
            ->count();
        $house_info = [
            'income' => round($income, 2),
            'spending' => round($spending, 2),
            'profit' => round($income - $spending, 2),
            'bill' => $bill,
            'contract' => $contract,
        ];
        return $this->returnWechat($house_info);
    }
}
