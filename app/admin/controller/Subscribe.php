<?php

namespace app\admin\controller;

use app\admin\model\AdminSubscribe as SubscribeModel;

class Subscribe
{
    private function getAccessToken()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . env('WECHAT_APP_ID') . "&secret=" . env('WECHAT_APP_SECRET');
        $result = json_decode(file_get_contents($url), true);
        $token = $result['access_token'];
        return $token;
    }

    public function execute()
    {
        // 微信小程序订阅消息模板ID
        $templateId = env('WECHAT_TEMPLATE_ID');
        $accessToken = $this->getAccessToken();
        $subscribeModel = new SubscribeModel(); // 实例化模型
        $subscribes = $subscribeModel->where('time2', '<=', date('Y-m-d 23:59:59'))->select();

        if (empty($subscribes)) {
            return json(['message' => 'No subscribes to process']);
        }

        $ch = curl_init();
        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$accessToken}";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $responseData = [];
        $deleteIds = [];

        foreach ($subscribes as $value) {
            $postData = [
                'touser' => $value['open_id'],
                'template_id' => $templateId,
                'page' => '/pages/index/index',
                'data' => [
                    'thing1' => ['value' => $value['thing1']],
                    'time2' => ['value' => substr($value['time2'], 0, 10)],
                ]
            ];

            $postJson = json_encode($postData);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postJson);

            // 使用ThinkPHP官方HTTP客户端发送请求
            $response = curl_exec($ch);
            // 检查是否有错误发生
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                // 记录错误日志或进行其他错误处理
                $responseData[] = ['error' => $error, 'subscribe_id' => $value['id']];
            } else {
                $responseData[] = json_decode($response, true);
                $deleteIds[] = $value['id'];
            }
        }
        // 关闭cURL会话
        curl_close($ch);

        // 批量删除记录
        if (!empty($deleteIds)) {
            $subscribeModel->whereIn('id', $deleteIds)->delete();
        }

        // 返回响应结果
        return json($responseData);
    }
}
