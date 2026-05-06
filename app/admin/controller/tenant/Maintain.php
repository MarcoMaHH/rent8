<?php

namespace app\admin\controller\tenant;

use app\admin\controller\Common;
use app\admin\library\Property;
use app\admin\model\TenantMaintain as MaintainModel;
use app\common\house\Maintain as MaintainAction;
use think\facade\View;

class Maintain extends Common
{
    public function index()
    {
        return View::fetch('/tenant/maintain/index');
    }
    public function queryMaintain()
    {
        $page = $this->request->param('page/d', LAYUI_PAGE);
        $limit = $this->request->param('limit/d', LAYUI_LIMIT);
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id],
        );
        $parameter = $this->request->param('parameter/s', '');
        if ($parameter) {
            $conditions[] = function ($query) use ($parameter) {
                $query->where('b.name', 'like', "%{$parameter}%")
                    ->whereOr('c.name', 'like', "%{$parameter}%")
                    ->whereOr('d.name', 'like', "%{$parameter}%");
            };
        }
        $count = MaintainModel::alias('a')
            ->join('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->where($conditions)
            ->count();
        $billing = MaintainModel::where($conditions)
            ->alias('a')
            ->join('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->field('a.*,b.name as number_name, c.name as property_name')
            // ->order(['a.create_time' => 'desc', 'number_name'])
            ->page($page, $limit)
            ->select();
        foreach ($billing as $value) {
            $value['request_time'] = \substr($value['create_time'], 0, 10);
        }
        return $this->returnResult($billing, $count);
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

    // 保存页面
    public function save()
    {
        $id = $this->request->get('id/d', 0);
        View::assign('id', $id);
        return View::fetch('/tenant/maintain/save');
    }
    public function queryMaintainEdit()
    {
        $id = $this->request->param('id/d', 0);
        $data = MaintainModel::where('a.id', $id)
            ->alias('a')
            ->join('HouseProperty b', 'a.house_property_id = b.id')
            ->join('HouseNumber c', 'a.house_number_id = c.id')
            ->field('a.*, b.name as property_name, c.name as number_name')
            ->select()->toArray();
        if (count($data) == 0) {
            return $this->returnError('维修单不存在');
        } else {
            $data[0]['accounting_date'] = date('Y-m-d');
            return $this->returnResult($data[0], 1, $data[0]['property_name'] . '-' . $data[0]['number_name'] . ' - 维修单');
        }
    }
    public function runSave()
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
}

