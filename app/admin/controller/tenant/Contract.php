<?php

namespace app\admin\controller\tenant;

use app\admin\controller\Common;
use app\admin\model\TenantContract as ContractModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\PhotoContract as PhotoModel;
use app\common\house\Contract as ContractAction;
use app\admin\library\Property;
use think\facade\View;

class Contract extends Common
{
    public function index()
    {
        return View::fetch('/tenant/contract/index');
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
                    ->whereOr('c.name', 'like', "%{$parameter}%")
                    ->whereOr('d.name', 'like', "%{$parameter}%");
            };
        }
        $count = ContractModel::alias('a')
            ->leftJoin('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->leftJoin('HouseProperty c', 'c.id = a.house_property_id')
            ->leftJoin('TenantMessage d', 'd.id = a.tenant_message_id')
            ->where($conditions)
            ->count();
        $contract = ContractModel::alias('a')
            ->leftJoin('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->leftJoin('HouseProperty c', 'c.id = a.house_property_id')
            ->leftJoin('TenantMessage d', 'd.id = a.tenant_message_id')
            ->field('a.*, b.name as number_name, c.name as property_name, d.name as tenant_name')
            ->where($conditions)
            ->orderRaw('a.type DESC, CASE WHEN a.end_date IS NULL THEN 1 ELSE 0 END, a.end_date ASC, a.house_property_id')
            ->select();
        foreach ($contract as $value) {
            if ($value['start_date']) {
                $value['start_date'] = \substr($value['start_date'], 0, 10);
            }
            if ($value['end_date']) {
                if ($value['type'] == 'Y') {
                    $value['remaining'] = \floor((\strtotime($value['end_date']) - \strtotime('today midnight')) / 86400);
                }
                $value['end_date'] = \substr($value['end_date'], 0, 10);
            }
        }
        return $this->returnResult($contract, $count);
    }
    public function delete()
    {
        $id = $this->request->param('id/d', null);
        $result = ContractAction::delete($id);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }
    // 合同下载
    public function contract()
    {
        $tenant_contract_id = $this->request->param('id/d', 0);
        $contract = ContractModel::find($tenant_contract_id);
        $msg_data = NumberModel::alias('a')
            ->leftjoin('HouseProperty b', 'a.house_property_id = b.id')
            ->leftjoin('TenantMessage c', 'a.tenant_message_id = c.id')
            ->where('a.id', $contract->house_number_id)
            ->field('a.rental, a.deposit, b.address, b.landlord, b.id_card as landlordId, b.name as property_name, a.name as number_name, a.equipment, c.name as renter, c.id_card_number')
            ->select()->toArray();

        $tmp = new \PhpOffice\PhpWord\TemplateProcessor('static/wordfile/contract.docx'); //打开模板
        $tmp->setValue('landlord', $msg_data[0]['landlord']); //替换变量name
        $tmp->setValue('landlordId', $msg_data[0]['landlordId']); //替换变量name
        $tmp->setValue('renter', $msg_data[0]['renter']);
        $tmp->setValue('renterId', $msg_data[0]['id_card_number']);
        $tmp->setValue('address', $msg_data[0]['address'] . $msg_data[0]['number_name']);
        $tmp->setValue('rental', Property::convert_case_number($msg_data[0]['rental']));
        $tmp->setValue('rentalLower', $msg_data[0]['rental']);
        $tmp->setValue('depositLower', $msg_data[0]['deposit']);
        $tmp->setValue('deposit', Property::convert_case_number($msg_data[0]['deposit']));
        $tmp->setValue('equipment', $msg_data[0]['equipment']);
        $startDate = explode('-',  \substr($contract->start_date, 0, 10));
        $endDate = explode('-', \substr($contract->end_date, 0, 10));
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
        header("Content-Disposition:attchment; filename=" . json_encode($msg_data[0]['property_name'] . '-' . $msg_data[0]['number_name'] . '-' . $msg_data[0]['renter'] . '合同.docx'));
        //输出文件内容
        echo fread($file_type, filesize($file_url));
        fclose($file_type);
    }

    // 合同编辑页面
    public function save()
    {
        $id = $this->request->get('id/d', 0);
        View::assign('id', $id);
        return View::fetch('/tenant/contract/save');
    }
    public function queryContractEdit()
    {
        $id = $this->request->param('id/d', 0);
        $data = ContractModel::where('a.id', $id)
            ->alias('a')
            ->join('HouseProperty b', 'a.house_property_id = b.id')
            ->join('HouseNumber c', 'a.house_number_id = c.id')
            ->join('TenantMessage d', 'a.tenant_message_id = d.id')
            ->field('a.*, b.name as property_name, c.name as number_name, d.name as tenant_name')
            ->select()->toArray();
        if (count($data) == 0) {
            return $this->returnError('合同不存在');
        } else {
            return $this->returnResult($data[0], 1, $data[0]['property_name'] . '-' . $data[0]['number_name'] . '-' . $data[0]['tenant_name'] . ' - 修改合同信息');
        }
    }
    public function runSave()
    {
        $data = [
            'id' => $this->request->post('id/d', 0),
            'house_property_id' => $this->request->post('house_property_id/s', '', 'trim'),
            'house_number_id' => $this->request->post('house_number_id/s', '', 'trim'),
            'tenant_message_id' => $this->request->post('tenant_message_id/s', '', 'trim'),
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

    // 照片上传页面
    public function photo()
    {
        $id = $this->request->get('id/d', 0);
        View::assign('id', $id);
        return View::fetch('/tenant/contract/photo');
    }
    // 合同照片页面
    public function upload()
    {
        $tenant_contract_id = $this->request->post('tenant_contract_id/s', '', 'trim');
        if (!$contract = ContractModel::find($tenant_contract_id)) {
            return $this->returnError('合同不存在');
        }
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
        $filePath = \think\facade\Filesystem::disk('public')->putFileAs('contract/' . $tenant_contract_id, $file, $newFileName);
        $data = [
            'house_property_id' => $contract->house_property_id,
            'house_bill_id' => $contract->house_bill_id,
            'tenant_contract_id' => $tenant_contract_id,
            'url' => '/storage/' . $filePath
        ];
        PhotoModel::create($data);
        return json(['code' => 1, 'msg' => '上传成功']);
    }
    // 查询照片信息
    public function queryPhoto()
    {
        $id = $this->request->param('id/d', 0);
        $contract = ContractModel::alias('a')
            ->leftjoin('HouseProperty b', 'a.house_property_id = b.id')
            ->leftjoin('HouseNumber c', 'a.house_number_id = c.id')
            ->leftjoin('TenantMessage d', 'a.tenant_message_id = d.id')
            ->field('a.*, b.name as property_name, c.name as number_name, d.name as tenant_name')
            ->find($id);
        $photo = PhotoModel::where('tenant_contract_id', $id)->select();
        foreach ($photo as $value) {
            $value['name'] = $value['url'];
        }
        return $this->returnResult($photo, 1, $contract['property_name'] . '-' . $contract['number_name'] . '-' . $contract['tenant_name'] . ' - 合同照片');
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
