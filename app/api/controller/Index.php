<?php

namespace app\api\controller;

use app\admin\model\HouseProperty as PropertyModel;
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
        $house_info = [
            'income' => $income,
            'spending' => $spending,
            'profit' => $income - $spending,
        ];
        return $this->returnWechat($house_info);
    }
}
