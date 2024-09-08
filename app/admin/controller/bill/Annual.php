<?php

namespace app\admin\controller\bill;

use app\admin\controller\Common;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\BillAnnual as AnnualModel;
use app\admin\model\BillSum as SumModel;
use think\facade\Db;
use think\facade\View;

class Annual extends Common
{
    public function index()
    {
        return View::fetch();
    }

    public function query()
    {
        $loginUser = $this->auth->getLoginUser();
        $annual = AnnualModel::where('a.admin_user_id', $loginUser['id'])
        ->alias('a')
        ->join('HouseProperty c', 'c.id = a.house_property_id')
        ->field('a.*, c.name as property_name')
        ->select();
        foreach ($annual as $value) {
            $value['profit'] = $value['income'] - $value['expenditure'];
        }

        $lasyYear = date('Y', strtotime('-1 year'));
        $propertys = PropertyModel::where('admin_user_id', $loginUser['id'])->select()->toArray();
        $allIncomes = SumModel::where('admin_user_id', $loginUser['id'])
        ->where('type', 'I')
        ->where('annual', $lasyYear)
        ->field('annual, house_property_id, sum(amount) as amount')
        ->group('annual, house_property_id')
        ->select()->toArray();
        $allExpenditures = SumModel::where('admin_user_id', $loginUser['id'])
            ->where('type', 'E')
            ->where('annual', $lasyYear)
            ->field('annual, house_property_id, sum(amount) as amount')
            ->group('annual, house_property_id')
            ->select()->toArray();
        foreach ($propertys as $property) {
            $propertyId = $property['id'];
            $income = 0;
            $expenditure = 0;

            // 从预先查询的数据中筛选当前年份和房产的收入和支出
            foreach ($allIncomes as $in) {
                if ($in['house_property_id'] == $propertyId) {
                    $income += $in['amount'];
                }
            }

            foreach ($allExpenditures as $ex) {
                if ($ex['house_property_id'] == $propertyId) {
                    $expenditure += $ex['amount'];
                }
            }

            if ($income > 0 || $expenditure > 0) {
                $annual[] = [
                    'annual' => $lasyYear,
                    'admin_user_id' => $loginUser['id'],
                    'property_name' => $property['name'],
                    'income' => $income,
                    'expenditure' => $expenditure,
                    'profit' => $income - $expenditure,
                ];
            }
        }
        return $this->returnElement($annual);
    }

    public function arrange()
    {
        $loginUser = $this->auth->getLoginUser();
        $years = SumModel::where('admin_user_id', $loginUser['id'])->field('annual')->group('annual')->select()->toArray();
        $propertys = PropertyModel::where('admin_user_id', $loginUser['id'])->select()->toArray();
        $result = [];

        // 预先查询所有相关收入和支出数据，避免在循环中多次查询数据库
        $allIncomes = SumModel::where('admin_user_id', $loginUser['id'])
            ->where('type', 'I')
            ->field('annual, house_property_id, sum(amount) as amount')
            ->group('annual, house_property_id')
            ->select()->toArray();

        $allExpenditures = SumModel::where('admin_user_id', $loginUser['id'])
            ->where('type', 'E')
            ->field('annual, house_property_id, sum(amount) as amount')
            ->group('annual, house_property_id')
            ->select()->toArray();

        foreach ($years as $yearData) {
            $year = $yearData['annual'];
            if ($year < date('Y') - 1) {
                foreach ($propertys as $property) {
                    $propertyId = $property['id'];
                    $income = 0;
                    $expenditure = 0;

                    // 从预先查询的数据中筛选当前年份和房产的收入和支出
                    foreach ($allIncomes as $in) {
                        if ($in['annual'] == $year && $in['house_property_id'] == $propertyId) {
                            $income += $in['amount'];
                        }
                    }

                    foreach ($allExpenditures as $ex) {
                        if ($ex['annual'] == $year && $ex['house_property_id'] == $propertyId) {
                            $expenditure += $ex['amount'];
                        }
                    }

                    if ($income > 0 || $expenditure > 0) {
                        $result[] = [
                            'annual' => $year,
                            'admin_user_id' => $loginUser['id'],
                            'house_property_id' => $propertyId,
                            'income' => $income,
                            'expenditure' => $expenditure,
                        ];
                    }
                }
            }
        }

        return $this->returnElement($result);
    }
}
