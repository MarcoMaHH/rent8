<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseContract as ContractModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\ContractPhoto as PhotoModel;
use app\common\house\Contract as ContractAction;
use app\admin\library\Property;
use app\admin\library\Date;
use think\facade\View;

class Contract extends Common
{
    public function index()
    {
        return View::fetch('/house/contract/index');
    }

    public function queryContract()
    {
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id],
        );
        $parameter = $this->request->param('parameter/s', '');
        if ($parameter) {
            $conditions[] = function ($query) use ($parameter) {
                $query->where('b.name', 'like', "%{$parameter}%")
                    ->whereOr('c.name', 'like', "%{$parameter}%");
            };
        }
        $count = ContractModel::alias('a')
            ->join('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->where($conditions)
            ->count();
        $contract = ContractModel::alias('a')
            ->join('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->field('a.*, b.name as number_name, c.name as property_name')
            ->where($conditions)
            ->orderRaw('CASE WHEN a.end_date IS NULL THEN 1 ELSE 0 END, a.end_date ASC, a.house_property_id')
            ->select();
        foreach ($contract as $value) {
            if ($value['start_date']) {
                $value['start_date'] = \substr($value['start_date'], 0, 10);
            }
            if ($value['end_date']) {
                $value['remaining'] = \floor((\strtotime($value['end_date']) - \strtotime('today midnight')) / 86400);
                $value['end_date'] = \substr($value['end_date'], 0, 10);
            }
        }
        return $this->returnResult($contract, $count);
    }

    // 合同管理页面-获取合同信息
    public function getContractMessage()
    {
        $house_property_id = Property::getProperty();
        // 合并查询，使用数据库的计算功能来得到有效和总合同数
        $contractQuery = ContractModel::where('house_property_id', 'in', $house_property_id);
        $totalContracts = $contractQuery->count(); // 总合同数
        $validContracts = $contractQuery->whereNotNull('end_date')->count(); // 有效合同数
        // 通过总数和有效数计算无效合同数
        $invalidContracts = $totalContracts - $validContracts;

        $contract_info = [
            'valid' => $validContracts,
            'invalid' => $invalidContracts,
        ];
        return $this->returnResult($contract_info);
    }

    public function save()
    {
        $data = [
            'id' => $this->request->post('id/d', 0),
            'house_property_id' => $this->request->post('house_property_id/s', '', 'trim'),
            'house_number_id' => $this->request->post('house_number_id/s', '', 'trim'),
            'start_date' => $this->request->post('start_date/s', '', 'trim'),
            'end_date' => $this->request->post('end_date/s', '', 'trim'),
        ];
        $result = ContractAction::save($data);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    // 合同
    public function contract()
    {
        $number_id = $this->request->param('id/d', 0);
        $number_data = NumberModel::where('a.id', $number_id)
            ->alias('a')
            ->leftjoin('HouseProperty b', 'a.house_property_id = b.id')
            ->leftjoin('HouseTenant c', 'a.tenant_id = c.id')
            ->field('a.*, b.address, b.landlord, b.id_card as landlordId, b.name as property_name, c.name as renter, c.id_card_number')
            ->select()->toArray();
        if (count($number_data) == 0) {
            return $this->returnError('房间不存在');
        }
        $tmp = new \PhpOffice\PhpWord\TemplateProcessor('static/wordfile/contract.docx'); //打开模板
        $tmp->setValue('landlord', $number_data[0]['landlord']); //替换变量name
        $tmp->setValue('landlordId', $number_data[0]['landlordId']); //替换变量name
        $tmp->setValue('renter', $number_data[0]['renter']);
        $tmp->setValue('renterId', $number_data[0]['id_card_number']);
        $tmp->setValue('address', $number_data[0]['address'] . $number_data[0]['name']);
        $tmp->setValue('rental', Property::convert_case_number($number_data[0]['rental']));
        $tmp->setValue('rentalLower', $number_data[0]['rental']);
        $tmp->setValue('depositLower', $number_data[0]['deposit']);
        $tmp->setValue('deposit', Property::convert_case_number($number_data[0]['deposit']));
        $tmp->setValue('equipment', $number_data[0]['equipment']);
        // $tmp->setValue('network', Property::convert_case_number($number_data[0]['network']));
        // $tmp->setValue('garbage_fee', Property::convert_case_number($number_data[0]['garbage_fee']));
        $startDate = explode('-', Date::getLease($number_data[0]['checkin_time'], $number_data[0]['lease'] - $number_data[0]['lease_type'])[0]);
        $endDate = explode('-', Date::getLease($number_data[0]['checkin_time'], $number_data[0]['lease'] + 11 - $number_data[0]['lease_type'])[1]);
        $tmp->setValue('startDate', $startDate[0] . '年' . $startDate[1] . '月' . $startDate[2] . '日');
        $tmp->setValue('endDate', $endDate[0] . '年' . $endDate[1] . '月' . $endDate[2] . '日');
        $tmp->saveAs('../tempfile/合同.docx'); //另存为
        $file_url = '../tempfile/合同.docx';
        // $file_name = basename($file_url);
        $file_type = explode('.', $file_url);
        $file_type = $file_type[count($file_type) - 1];
        $file_type = fopen($file_url, 'r'); //打开文件
        //输入文件标签
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: " . filesize($file_url));
        header("Content-Disposition:attchment; filename=" . json_encode($number_data[0]['property_name'] . '-' . $number_data[0]['name'] . '合同.docx'));
        //输出文件内容
        echo fread($file_type, filesize($file_url));
        fclose($file_type);
    }

    // 合同照片
    public function upload()
    {
        $way = $this->request->post('way/s', '', 'trim');
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        $originalName = $file->getOriginalName();

        // 提取文件名（不含扩展名）和扩展名
        $fileNameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

        // 生成随机数
        $randomNum = mt_rand(1000, 9999); // 生成一个4位的随机数

        // 组合新的文件名
        $newFileName = $fileNameWithoutExt . '_' . $randomNum . '.' . $extension;

        // 上传到本地服务器
        $filePath = \think\facade\Filesystem::disk('public')->putFileAs('contract/' . $way, $file, $newFileName);
        $house_property_id = $this->request->post('house_property_id/s', null, 'trim');
        $house_number_id = $this->request->post('house_number_id/s', null, 'trim');
        $data = [
            'house_property_id' => $house_property_id,
            'house_number_id' => $house_number_id,
            'contract_id' => $way,
            'url' => '/storage/' . $filePath
        ];
        PhotoModel::create($data);
        return json(['code' => 1, 'msg' => '上传成功']);
    }

    // 查询照片信息
    public function queryPhoto()
    {
        $id = $this->request->param('id/d', 0);
        $photo = PhotoModel::where('contract_id', $id)->select();
        foreach ($photo as $value) {
            $value['name'] = $value['url'];
        }
        return $this->returnResult($photo);
    }

    // 删除照片
    public function deletePhoto()
    {
        $id = $this->request->post('id/d', 0);
        if (!$photo = PhotoModel::find($id)) {
            return $this->returnError('删除失败，记录不存在。');
        }
        $photo->delete();
        $photoPath = app()->getRootPath() . 'public' . $photo['url'];
        if (file_exists($photoPath) && !unlink($photoPath)) {
            return $this->returnError('删除失败，文件无法删除。');
        }
        return $this->returnSuccess('删除成功');
    }
}
