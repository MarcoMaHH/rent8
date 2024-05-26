<?php

namespace app\admin\library;

use app\common\library\Tree;

class Menu extends Tree
{
    public function getTree($curr = '')
    {
        $data = $this->data;
        // foreach ($data as $k => $v) {
        //     $data[$k]['curr'] = $this->isCurr($v['controller'], $curr);
        // }
        return $this->tree($data, 0);
    }
    protected function isCurr($test, $curr)
    {
        return ($test === $curr) || ($test . '.' === substr($curr, 0, strlen($test) + 1));
    }
    public function getCurrentRoute($curr = '')
    {
        $result = ['id' => '0', 'pid' => '0'];
        $data = $this->data;
        foreach ($data as $k => $v) {
            if ($v['controller'] === $curr) {
                $result['id'] = $v['id'];
                $result['pid'] = $v['pid'];
                break;
            }
        }
        return $result;
    }
}
