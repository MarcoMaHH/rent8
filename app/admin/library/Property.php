<?php

namespace app\admin\library;

use app\admin\model\HouseProperty as PropertyModel;

class Property
{
    /**
     * 获取默认房产id
     */
    public static function getProperty($user = null)
    {
        $result = 0;
        if ($user) {
            $properties = PropertyModel::where('admin_user_id', $user)->order(['firstly'=>'desc', 'id'])->find();
            if ($properties) {
                $result = $properties['id'];
            }
        }
        return $result;
    }

    /**
     * 数字金额转换大写数字
     * @param [float] $num 数字类型
     * @return void
     */
    public static function convert_case_number($num)
    {
        //转换数字类型
        $num = (float) $num;
        //判断$num是否存在
        if (!$num) {
            return '零圆';
        }
        //保留小数点后两位
        $num = sprintf("%.2f", $num);
        //将浮点转换为整数
        $tem_num = $num * 100;
        //判断数字长度
        $tem_num_len = strlen($tem_num);
        if ($tem_num_len > 14) {
            return '数字过大';
        }
        //大写数字
        $dint = array('零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖');
        //大写金额单位
        $danwei = array('仟', '佰', '拾', '亿', '仟', '佰', '拾', '万', '仟', '佰', '拾', '元');
        $danwei1 = array('角', '分');
        //空的变量用来保存转换字符串
        $daxie = '';
        //分割数字，区分圆角分
        list($left_num, $right_num) = explode('.', $num);
        //计算单位长度
        $danwei_len = count($danwei);
        //计算分割后的字符串长度
        $left_num_len = strlen($left_num);
        $right_num_len = strlen($right_num);
        //循环计算亿万元等
        for ($i = 0; $i < $left_num_len; $i++) {
            //循环单个文字
            $key_ = substr($left_num, $i, 1);
            //判断数字不等于0或数字等于0与金额单位为亿、万、圆，就返回完整单位的字符串
            if ($key_ !== '0' || ($key_ == '0' && ($danwei[$danwei_len - $left_num_len + $i] == '亿' || $danwei[$danwei_len - $left_num_len + $i] == '万' || $danwei[$danwei_len - $left_num_len + $i] == '圆'))) {
                $daxie = $daxie . $dint[$key_] . $danwei[$danwei_len - $left_num_len + $i];
            } else {
                //否则就不含单位
                $daxie = $daxie . $dint[$key_];
            }
        }
        //循环计算角分
        for ($i = 0; $i < $right_num_len; $i++) {
            $key_ = substr($right_num, $i, 1);
            if ($key_ > 0) {
                $daxie = $daxie . $dint[$key_] . $danwei1[$i];
            }
        }

        //计算转换后的长度
        $daxie_len = strlen($daxie);
        //设置文字切片从0开始，utf-8汉字占3个字符
        $j = 0;
        while ($daxie_len > 0) {
            //每次切片两个汉字
            $str = substr($daxie, $j, 6);
            //判断切片后的文字不等于零万、零圆、零亿、零零
            if ($str == '零万' || $str == '零圆' || $str == '零亿' || $str == '零零') {
                //重新切片
                $left = substr($daxie, 0, $j);
                $right = substr($daxie, $j + 3);
                $daxie = $left . $right;
            }
            $j += 3;
            $daxie_len -= 3;
        }
        return $daxie . '整';
    }
}
