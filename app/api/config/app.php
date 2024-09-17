<?php

return [
    'default_return_type' => 'json',

    'http_exception_template'    =>  [
        // 定义999错误 还没有登陆
        999 =>  \think\facade\App::getAppPath() . '999.json',
        888 =>  \think\facade\App::getAppPath() . '888.json',
    ]
];
