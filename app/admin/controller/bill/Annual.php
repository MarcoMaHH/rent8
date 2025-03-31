<?php

namespace app\admin\controller\bill;

use app\admin\controller\Common;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\BillAnnual as AnnualModel;
use app\admin\model\BillSum as SumModel;
use app\admin\library\Property;
use think\facade\Db;
use think\facade\View;

class Annual extends Common
{
    public function index()
    {
        return View::fetch('/bill/annual/index');
    }

    public function query()
    {
        $loginUser = $this->auth->getLoginUser();
        $result = Property::getProperty();
        $annual = AnnualModel::where('a.house_property_id', 'in', $result)
            ->alias('a')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->field('a.*, c.name as property_name')
            ->order(['a.annual' => 'desc', 'c.name'])
            ->select()
            ->toArray();
        foreach ($annual as $key => $value) {
            $annual[$key]['profit'] = $value['income'] - $value['expenditure'];
        }

        $lasyYear = date('Y', strtotime('-1 year'));
        $propertys = PropertyModel::where('admin_user_id', $loginUser['id'])->select()->toArray();
        $allIncomes = SumModel::where('house_property_id', 'in', $result)
            ->where('type', 'I')
            ->where('annual', $lasyYear)
            ->field('annual, house_property_id, sum(amount) as amount')
            ->group('annual, house_property_id')
            ->select()->toArray();
        $allExpenditures = SumModel::where('house_property_id', 'in', $result)
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
                array_unshift($annual, [
                    'annual' => $lasyYear,
                    'admin_user_id' => $loginUser['id'],
                    'property_name' => $property['name'],
                    'income' => $income,
                    'expenditure' => round($expenditure, 2),
                    'profit' => round($income - $expenditure, 2),
                ]);
            }
        }
        return $this->returnResult($annual);
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

        // 开始事务
        $transFlag = true;
        Db::startTrans();
        try {
            foreach ($result as $item) {
                if ($annual = AnnualModel::where('admin_user_id', $item['admin_user_id'])
                    ->where('house_property_id', $item['house_property_id'])
                    ->where('annual', $item['annual'])
                    ->find()
                ) {
                    $annual->income += $item['income'];
                    $annual->expenditure += $item['expenditure'];
                    $annual->save();
                } else {
                    AnnualModel::create($item);
                }
                SumModel::where('annual', $item['annual'])
                    ->where('admin_user_id', $item['admin_user_id'])
                    ->where('house_property_id', $item['house_property_id'])
                    ->delete();
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
            return $this->returnSuccess('整理成功');
        }
    }
}
