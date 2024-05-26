<?php

namespace app\api\controller;

use app\admin\library\Property;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\BillSum as SumModel;
use app\admin\model\AdminUser as UserModel;

class Report extends Common
{
    public function query()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $accounting_month = date('Y-m');
        $income = SumModel::where('house_property_id', $house_property_id)
        ->where('type', TYPE_INCOME)
        ->where('accounting_date', $accounting_month)
        ->sum('amount');
        // todo
        $spending = SumModel::where('house_property_id', $house_property_id)
        ->where('type', TYPE_EXPENDITURE)
        ->where('accounting_date', $accounting_month)
        ->sum('amount');
        $user = UserModel::find($loginUser['id']);
        $number_count =  $user->houseNumber->where('house_property_id', $house_property_id)->count();
        $empty_count =  $user->houseNumber->where('rent_mark', 'N')->where('house_property_id', $house_property_id)->count();
        $occupancy = $number_count == 0 ? '0%' : round((($number_count - $empty_count) / $number_count) * 100).'%';
        $house_info = [
            'income' => $income,
            'spending' => round($spending, 2),
            'profit' => round($income - $spending, 2),
            'occupancy' => $occupancy,
            'number_count' => $number_count,
            'empty_count' => $empty_count,
        ];
        return $this->returnWechat($house_info);
    }
}
