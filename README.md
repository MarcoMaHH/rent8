# rent8-最常用出租屋管理系统-包租婆版

自家用的出租屋管理系统。本系统基于ThinkPHP8(PHP8)+TDesign(Vue3)开发，混编版前后端不分离。
本系统有扣子智能管家，有疑问的可以直接问智能管家。

**因为包租婆版没有合租功能，所以没有账单管理页面，操作更加简单。**

[rent8-最常用出租屋管理系统-微信小程序端](https://gitee.com/MarcoMaHH/rent8_wechat/tree/包租婆/)

[rent8-最常用出租屋管理系统-安装教程](https://mp.weixin.qq.com/s/3doZqTsTW9m9RhcBkeAEGg)

[rent8-最常用出租屋管理系统-使用说明](https://mp.weixin.qq.com/s/zHunfQ6ndL_IXOCl38mwXQ)

[rent8-最常用出租屋管理系统-扣子AI智能客服](https://www.coze.cn/store/agent/7462019405607010343?bid=6fntqflf06019&bot_id=true)

### 核心功能介绍

- 房产管理：新增房产信息、修改房产信息、删除房产信息。

- 房间管理：新增房间信息、修改房间信息、删除房间信息、入住管理、退房管理、闲置天数记录。

- 账单管理：修改账单、抄表（包括集中抄电表、集中抄水表）、收据单生成、到账确认、延期处理、账单详情查看、今日到账金额统计。

- 租客档案管理：修改租客信息、上传租客证件照片。

- 合同管理：修改合同内容、下载合同文件、合同剩余天数查看。

- 其他支出管理：维修费记录、工资等支出记录。

- 申请维护管理：查看、处理租客维修申请

- 水电费账单管理：记录总水表金额及用量、记录总电表金额及用量、计算差价及差额。

- 房产报表生成：本月收入、支出、利润统计、年度财务收支走势分析。

### 界面展示

![登录页面](https://gitee.com/MarcoMaHH/picture/raw/master/rent8/包租婆/login.jpg)

![主页面](https://gitee.com/MarcoMaHH/picture/raw/master/rent8/包租婆/index.jpg)

![房产管理-页面](https://gitee.com/MarcoMaHH/picture/raw/master/rent8/包租婆/property.jpg)

![房间管理-页面](https://gitee.com/MarcoMaHH/picture/raw/master/rent8/包租婆/number.jpg)

![未收账单-页面](https://gitee.com/MarcoMaHH/picture/raw/master/rent8/包租婆/uncollected.jpg)

![收据单-展示](https://gitee.com/MarcoMaHH/picture/raw/master/rent8/包租婆/receipt.jpg)

![到账账单-页面](https://gitee.com/MarcoMaHH/picture/raw/master/rent8/包租婆/collected.jpg)

![其他支出-页面](https://gitee.com/MarcoMaHH/picture/raw/master/rent8/包租婆/other.jpg)

![租客信息-页面](https://gitee.com/MarcoMaHH/picture/raw/master/rent8/包租婆/tenant.jpg)

![合同管理-页面](https://gitee.com/MarcoMaHH/picture/raw/master/rent8/包租婆/contract.jpg)

![租聘合同-展示](https://gitee.com/MarcoMaHH/picture/raw/master/rent8/包租婆/contract_show.jpg)

![维护申请-页面](https://gitee.com/MarcoMaHH/picture/raw/master/rent8/包租婆/maintain.jpg)

![用户管理-页面](https://gitee.com/MarcoMaHH/picture/raw/master/rent8/包租婆/user.jpg)

### 系统环境

- PHP = 8.2.26
- Apache = 2.4.41
- MySQL = 5.7.28

PHP需安装的扩展
curl，mbstring，mysqli，openssl，zip，pdo_mysql，fileinfo

### 技术栈

- ThinkPHP8（PHP8）
- TDesign（Vue3）
- 扣子AI智能体
- antV G2
- printJS

### 安装步骤

1. 建立数据库`rent8`
2. `git clone -b 包租婆 https://gitee.com/MarcoMaHH/rent82.git`
3. `cd rent82`
4. 将.example.env改为.env，并修改其中的数据库用户名和密码,如果使用微信小程序，则还需要填写appID，appSecret和templateId（订阅消息模版）。
5. `php think migrate:run`
6. `php think seed:run`

PS:宝塔面板安装的，需要在禁用函数里移除putenv()、pcntl_signal()、proc_open()这3个函数。

### 订阅消息提醒

服务器配置方式：

1. Linux cron 配置 ：

   ```bash
   # 每天上午9点执行（需替换实际域名）
   0 9 * * * curl "http://yourdomain.com/admin/subscribe/execute"
   ```

2. Windows 任务计划 ：

   ```bash
   schtasks /create /tn "WechatSubscribe" /tr "curl http://yourdomain.com/admin/subscribe/execute" /sc DAILY /st 09:00
   ```

### 原始账号密码

账号：admin  密码：123456

账号：user     密码：123456
