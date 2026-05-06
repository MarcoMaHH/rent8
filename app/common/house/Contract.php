<?php

namespace app\common\house;

use app\admin\model\TenantContract as ContractModel;
use app\admin\model\PhotoContract as PhotoModel;

class Contract
{
    public static function save($data)
    {
        if (!$contract = ContractModel::find($data['id'])) {
            return ['flag' => false, 'msg' => '保存失败，合同不存在。'];
        }
        $contract->save($data);
        return ['flag' => true, 'msg' => '修改成功'];
    }

    public static function delete($id)
    {
        if (!$contract = ContractModel::find($id)) {
            return ['flag' => false, 'msg' => '删除失败，合同不存在。'];
        }
        $photoRootPath = app()->getRootPath() . 'public';
        $folders = [];
        PhotoModel::where('tenant_contract_id', '=', $id)->chunk(100, function ($photos) use ($photoRootPath, &$folders) {
            foreach ($photos as $photo) {
                $filePath = $photoRootPath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice(explode('/', $photo['url']), 1));
                $folderPath = dirname($filePath);
                $folders[$folderPath] = true;
                if (file_exists($filePath)) {
                    try {
                        unlink($filePath);
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        });

        // 尝试删除所有空文件夹
        foreach (array_keys($folders) as $folderPath) {
            try {
                if (is_dir($folderPath) && count(scandir($folderPath)) === 2) {
                    rmdir($folderPath);
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        PhotoModel::where('tenant_contract_id', '=', $id)->delete();
        $contract->delete();
        return ['flag' => true, 'msg' => '删除成功'];
    }
}
