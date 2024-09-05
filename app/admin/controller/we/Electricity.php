<?php

namespace app\admin\controller\we;

use app\admin\controller\Common;
use app\admin\model\WeBill as WeBillModel;
use app\admin\model\WeMeter as MeterModel;
use app\admin\model\WeDetail as WeDetailModel;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\library\Property;
use app\admin\model\BillSum as SumModel;
use think\facade\View;
use think\facade\Db;

class Electricity extends Common
{
    public function index()
    {
        return View::fetch();
    }

    //查询电费
    public function queryElectricity()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $conditions = array(
            ['b.house_property_id', '=', $house_property_id],
            ['b.type', '=', TYPE_ELECTRICITY]
        );
        $meter_id = $this->request->param('meter_id/s', '', 'trim');
        if ($meter_id) {
            \array_push($conditions, ['b.id', '=', $meter_id]);
        }
        $count = WeBillModel::alias('a')
        ->join('WeMeter b', 'a.meter_id = b.id')
        ->where($conditions)
        ->count();
        $water = WeBillModel::alias('a')
        ->join('WeMeter b', 'a.meter_id = b.id')
        ->where($conditions)
        ->order(['a.start_month' => 'desc', 'b.type'])
        ->field('a.id, a.accounting_date, a.end_month, a.start_month, b.id as meter_id, b.type, b.house_property_id, b.name as electricity_name, a.master_sum, a.master_dosage')
        ->select();
        $result = [];
        foreach ($water as $value) {
            $detail = WeDetailModel::where('meter_id', $value['meter_id'])
            ->where('type', $value['type'])
            ->where('calculate_date', 'between time', [$value['start_month'] , $value['end_month']])
            ->field('sum(amount) as amount, sum(dosage) as dosage')
            ->select()->toArray();
            if (count($detail)) {
                if ($detail[0]['amount']) {
                    $value['detail_sum'] = round($detail[0]['amount'], 2);
                } else {
                    $value['detail_sum'] = null;
                }
                $value['detail_dosage'] = $detail[0]['dosage'];
            }
            if ($value['start_month']) {
                $value['start_month'] = \substr($value['start_month'], 0, 10);
            }
            if ($value['end_month']) {
                $value['end_month'] = \substr($value['end_month'], 0, 10);
            }
            \array_push($result, $value);
        }
        return $this->returnElement($water, $count);
    }

    // 查询电表
    public function queryMeter()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $electricity = MeterModel::where(
            ['house_property_id' => $house_property_id,
            'type' => TYPE_ELECTRICITY]
        )->select();
        return $this->returnElement($electricity);
    }

    // 保存电费
    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $meter_id = $this->request->param('meter_id/d', 0);
        $loginUser = $this->auth->getLoginUser();
        $data = [
            'meter_id' => $meter_id,
            'house_property_id' => Property::getProperty($loginUser['id']),
            'start_month' => $this->request->post('start_month/s', '', 'trim'),
            'end_month' => $this->request->post('end_month/s', '', 'trim'),
            'master_dosage' => $this->request->param('master_dosage/d', 0),
            'master_sum' => $this->request->param('master_sum/f', 0.0),
        ];
        if (!$meterArr = MeterModel::find($meter_id)) {
            $this->error('保存失败，记录不存在。');
        }
        $data['end_month'] = $data['end_month'] . ' 23:59:59';
        if ($id) {
            if (!$water = WeBillModel::find($id)) {
                $this->error('修改失败，记录不存在。');
            }
            $water->save($data);
            $this->success('修改成功。');
        }
        WeBillModel::create($data);
        $this->success('添加成功。');
    }

    // // 删除
    // public function delete()
    // {
    //     $id = $this->request->param('id/d', 0);
    //     if (!$water = WeBillModel::find($id)) {
    //         $this->error('删除失败,记录不存在。');
    //     }
    //     $water->delete();
    //     $this->success('删除成功');
    // }

    // 到账
    public function account()
    {
        $id = $this->request->param('id/d', 0);
        if (!$water = WeBillModel::whereNull('accounting_date')
            ->whereNotNull('end_month')->find($id)) {
            $this->error('不符合到账条件');
        }
        $transFlag = true;
        Db::startTrans();
        try {
            $water->save(['accounting_date' => date('Y-m-d', time())]);
            //总表记录
            $totalData = WeBillModel::where('a.id', $id)->alias('a')
            ->join('WeMeter b', 'a.meter_id = b.id')
            ->field('b.type, b.house_property_id, a.master_sum')
            ->find();
            WeBillModel::create([
                'meter_id' => $water->meter_id,
                'house_property_id' => $totalData->house_property_id,
                'start_month' => date("Y-m-d", strtotime("+1 day", strtotime($water->end_month))),
            ]);
            $accounting_month = date('Y-m');
            $sum_data = SumModel::where([
                'house_property_id' => $totalData->house_property_id,
                'type' => TYPE_EXPENDITURE,
                'accounting_date' => $accounting_month,
            ])->find();
            if ($sum_data) {
                $sum_data->save([
                    'amount' => $sum_data->amount + $totalData->master_sum,
                ]);
            } else {
                SumModel::create([
                    'house_property_id' => $totalData->house_property_id,
                    'amount' => $totalData->master_sum,
                    'type' => TYPE_EXPENDITURE,
                    'accounting_date' => $accounting_month
                ]);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            Db::rollback();
            // 回滚事务
            $this->error($e->getMessage());
        }
        if ($transFlag) {
            $this->success('操作成功');
        }
    }
}
