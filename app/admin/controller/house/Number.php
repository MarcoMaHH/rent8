<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\HouseBilling as BillingModel;
use app\admin\model\AdminUser as UserModel;
use app\common\house\Number as NumberAction;
use app\admin\library\Property;
use app\admin\library\Date;
use think\facade\Db;
use think\facade\View;

class Number extends Common
{
    public function index()
    {
        return View::fetch();
    }

    public function queryNumber()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $name = $this->request->param('name/s', '', 'trim');
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id],
        );
        if ($name != '') {
            \array_push($conditions, ['a.name', 'like', '%' . $name . '%']);
        }
        $numbers = NumberModel::where($conditions)
        ->alias('a')
        ->join('HouseProperty b', 'a.house_property_id = b.id')
        ->field('a.*,b.name as property_name')
        ->order('a.name')
        ->select();
        foreach ($numbers as $value) {
            if ($value['lease']) {
                $value['rent_date'] = Date::getLease($value['checkin_time'], $value['lease'] - $value['lease_type'])[0];
            }
            if ($value['checkin_time']) {
                $value['checkin_time'] = \substr($value['checkin_time'], 0, 10);
            }
        }
        return $this->returnResult($numbers);
    }

    public function getMessage()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $user = UserModel::find($loginUser['id']);
        $number_count =  $user->houseNumber->where('house_property_id', $house_property_id)->count();
        $empty_count =  $user->houseNumber->where('rent_mark', 'N')->where('house_property_id', $house_property_id)->count();
        $occupancy = $number_count == 0 ? '0%' : round((($number_count - $empty_count) / $number_count) * 100).'%';
        $number_info = [
            'occupancy' => $occupancy,
            'rented' => $number_count - $empty_count,
            'empty' => $empty_count,
        ];
        return $this->returnResult($number_info);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'house_property_id' => $this->request->post('house_property_id/d', 0),
            'name' => $this->request->post('name/s', '', 'trim'),
            'rental' => $this->request->post('rental/d', 0),
            'deposit' => $this->request->post('deposit/d', 0),
            'lease_type' => $this->request->post('lease_type/d', 0),
            'management' => $this->request->post('management/d', 0),
            'network' => $this->request->post('network/d', 0),
            'garbage_fee' => $this->request->post('garbage_fee/d', 0),
            'daily_rent' => $this->request->post('daily_rent/d', 0),
            'water_price' => $this->request->post('water_price/f', 0.0),
            'electricity_price' => $this->request->post('electricity_price/f', 0.0),
        ];
        $result = NumberAction::save($id, $data);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    // 批量保存
    public function saveMore()
    {
        $numbdrData = $this->request->post();

        // 开始事务
        $transFlag = true;
        Db::startTrans();
        try {
            foreach ($numbdrData as $item) {
                if (NumberModel::where('name', $item['name'])
                    ->where('house_property_id', $item['house_property_id'])
                    ->find()) {
                    throw new \Exception('该房间已存在，请勿重复添加');
                }
                NumberModel::create($item);
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
            return $this->returnSuccess('新建成功');
        }
    }

    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        if (!$number = NumberModel::find($id)) {
            return $this->returnError('删除失败,房间不存在。');
        }

        // 开始事务
        $transFlag = true;
        Db::startTrans();
        try {
            BillingModel::where('house_property_id', $number['house_property_id'])
            ->where('house_number_id', $number['id'])
            ->delete();
            $number->delete();
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            // 回滚事务
            Db::rollback();
            return $this->returnError($e->getMessage());
        }
        if ($transFlag) {
            return $this->returnSuccess('删除成功');
        }
    }

    //新租
    public function checkin()
    {
        $data = [
            'house_property_id' => $this->request->post('house_property_id/d', 0),
            'house_number_id' => $this->request->post('house_number_id/d', 0),
            'checkin_time' => $this->request->post('checkin_time/s', '', 'trim'),
        ];
        $result = NumberAction::checkin($data);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    //退房
    public function checkout()
    {
        $number_id = $this->request->param('id/d', 0);
        $leave_time = $this->request->param('leave_time/s', date('Y-m-d'), 'trim');
        $result = NumberAction::checkout($number_id, $leave_time);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    //其他页面查询numberId
    public function queryNumberId()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $number = NumberModel::where('house_property_id', $house_property_id)
        ->order('name')
        ->field('id as value,name as label')
        ->select()
        ->toArray();
        return $this->returnResult($number);
    }

    public function contract()
    {
        $number_id = $this->request->param('id/d', 0);
        // $number_id = 0;
        $number_data = NumberModel::where('a.id', $number_id)
        ->alias('a')
        ->join('HouseProperty b', 'a.house_property_id = b.id')
        ->leftJoin('HouseTenant c', 'a.tenant_id = c.id')
        ->field('a.*,b.address, b.landlord, b.id_card as landlordId, c.name as renter, c.id_card_number')
        ->select()->toArray();
        if (!$number_data) {
            return $this->returnError('修改失败，记录不存在');
        }
        // var_dump($number_data);
        $tmp = new \PhpOffice\PhpWord\TemplateProcessor('static/wordfile/contract.docx');//打开模板
        $tmp->setValue('landlord', $number_data[0]['landlord']);//替换变量name
        $tmp->setValue('landlordId', $number_data[0]['landlordId']);//替换变量name
        $tmp->setValue('renter', $number_data[0]['renter']);
        $tmp->setValue('renterId', $number_data[0]['id_card_number']);
        $tmp->setValue('address', $number_data[0]['address'] . $number_data[0]['name']);
        $tmp->setValue('rental', Property::convert_case_number($number_data[0]['rental']));
        $tmp->setValue('rentalLower', $number_data[0]['rental']);
        $tmp->setValue('depositLower', $number_data[0]['deposit']);
        $tmp->setValue('deposit', Property::convert_case_number($number_data[0]['deposit']));
        // $tmp->setValue('management', Property::convert_case_number($number_data[0]['management']));
        // $tmp->setValue('network', Property::convert_case_number($number_data[0]['network']));
        // $tmp->setValue('garbage_fee', Property::convert_case_number($number_data[0]['garbage_fee']));
        $startDate = explode('-', Date::getLease($number_data[0]['checkin_time'], $number_data[0]['lease'] - $number_data[0]['lease_type'])[0]);
        $endDate = explode('-', Date::getLease($number_data[0]['checkin_time'], $number_data[0]['lease'] + 11 - $number_data[0]['lease_type'])[1]);
        $tmp->setValue('startDate', $startDate[0] . '年' . $startDate[1] . '月' . $startDate[2] . '日');
        $tmp->setValue('endDate', $endDate[0] . '年' . $endDate[1] . '月' . $endDate[2] . '日');
        $tmp->saveAs('../tempfile/合同.docx');//另存为
        $file_url = '../tempfile/合同.docx';
        $file_name = basename($file_url);
        $file_type = explode('.', $file_url);
        $file_type = $file_type[count($file_type) - 1];
        $file_type = fopen($file_url, 'r'); //打开文件
        //输入文件标签
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: " . filesize($file_url));
        header("Content-Disposition:attchment; filename=" . json_encode($number_data[0]['name'].'合同.docx'));
        //输出文件内容
        echo fread($file_type, filesize($file_url));
        fclose($file_type);
    }
}
