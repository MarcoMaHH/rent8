<?php

namespace app\admin\controller;

use app\admin\model\AdminSubscribe as SubscribeModel;
use Exception;

// adminSubscribe类型
//类型-续约
define('TYPE_RENEWAL', 'A');
//类型-收租
define('TYPE_RENT', 'B');

class Subscribe
{
    public function link()
    {
        $maxRetries = 5;
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            try {
                // 替换 ping() 方法为简单的数据库查询
                $result = \think\facade\Db::query('SELECT 1');
                if ($result) {
                    file_put_contents('php://stdout', 'subscribe link success' . PHP_EOL);
                    return;
                }
            } catch (Exception $e) {
                $retryCount++;
                // 添加带延时的重连机制
                sleep(pow(2, $retryCount));
                \think\facade\Db::reconnect();
            }
        }
        // 替换原来的异常抛出为结构化日志输出
        $logMessage = json_encode([
            'severity' => 'CRITICAL',
            'message' => "数据库连接失败: 已尝试 {$maxRetries} 次重连",
            'timestamp' => date('c')
        ]);
        file_put_contents('php://stdout', $logMessage . PHP_EOL);
        return;
    }

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
        $rentTemplate = env('WECHAT_RENT_TEMPLATE');
        $renewalTemplate = env('WECHAT_RENEWAL_TEMPLATE');
        $accessToken = $this->getAccessToken();
        $subscribeModel = new SubscribeModel(); // 实例化模型
        $subscribes = $subscribeModel->where('time', '<=', date('Y-m-d 23:59:59'))->select();

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
                'page' => '/pages/index/index',
            ];
            if ($value['subscribe_type'] == TYPE_RENEWAL) {
                $postData['template_id'] = $renewalTemplate;
                $postData['data']['thing2'] = ['value' => $value['thing']];
                $postData['data']['time5'] = ['value' => substr($value['time'], 0, 10)];
            } else if ($value['subscribe_type'] == TYPE_RENT) {
                $postData['template_id'] = $rentTemplate;
                $postData['data']['thing1'] = ['value' => $value['thing']];
                $postData['data']['time2'] = ['value' => substr($value['time'], 0, 10)];
                $postData['data']['amount4'] = ['value' => $value['amount']];
            }

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
