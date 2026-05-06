<?php

namespace app\admin\controller\admin;

use app\admin\controller\Common;
use app\admin\model\AdminParameter as ParameterModel;

class Parameter extends Common
{
    public function queryParameter()
    {
        $parameter = $this->request->param('parameter/s', '');
        $data = ParameterModel::where('name', $parameter)->select();
        return $this->returnResult($data);
    }
}
