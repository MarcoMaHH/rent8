<?php

namespace app\api\controller;

use app\admin\library\Property;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\BillSum as SumModel;
use app\admin\model\AdminUser as UserModel;

class Index extends Common
{
    public function query()
    {
        $loginUser = $this->auth->getLoginUser();
        $user = UserModel::find($loginUser['id']);
        $number_count =  $user->houseNumber->count();
        $empty_count =  $user->houseNumber->where('rent_mark', 'N')->count();
        $occupancy = $number_count == 0 ? '0%' : round((($number_count - $empty_count) / $number_count) * 100) . '%';
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
            'income' => $income - intval($spending),
            'empty_count' => $empty_count,
            'occupancy' => $occupancy,
        ];
        return $this->returnWechat($house_info);
    }
}
