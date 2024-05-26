<?php
namespace app\common\library;

class Tree
{
    protected $idName = 'id';
    protected $pidName = 'pid';
    protected $subName = 'sub';
    protected $data = [];
    protected $levelName = 'level';

    public function __construct(array $data = [])
    {
        $this->data($data);
    }

    public function data(array $data = [])
    {
        $this->data = $data;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getTree()
    {
        return $this->tree($this->data);
    }

    public function tree($data, $pid = 0)
    {
        $result = [];
        foreach ($data as $v) {
            if ($v[$this->pidName] === $pid) {
                $sub = $this->tree($data, $v[$this->idName]);
                $v[$this->subName] = $sub;
                $result[] = $v;
            }
        }
        return $result;
    }

    public function treeList($data, $pid = 0, $level = 0, &$tree = [])
    {
        foreach ($data as $v) {
            if ($v[$this->pidName] == $pid) {
                $v[$this->levelName] = $level;
                $tree[] = $v;
                $this->treeList($data, $v['id'], $level + 1, $tree);
            }
        }
        return $tree;
    }

    public function getTreeList()
    {
        return $this->treeList($this->data);
    }

    public function getTreeListEle()
    {
        $data = $this->getTreeList();
        $result = [];
        foreach ($data as $k => $v) {
            if ($v[$this->pidName] === 0) {
                $children = [];
                foreach ($data as $vv) {
                    if ($vv[$this->pidName] === $v[$this->idName]) {
                        array_push($children, $vv);
                    }
                }
                $v['children'] = $children;
                array_push($result, $v);
            }
        }
        return $result;
    }

    public function getTreeListCheckLeaf($name = 'isLeaf')
    {
        $data = $this->getTreeList();
        foreach ($data as $k => $v) {
            foreach ($data as $vv) {
                $data[$k][$name] = true;
                if ($v[$this->idName] === $vv[$this->pidName]) {
                    $data[$k][$name] = false;
                    break;
                }
            }
        }
        return $data;
    }
}
