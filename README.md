出租屋管理系统微信小程序版，免费使用，适合白嫖党，小白党！

![最常用房东助手](https://gitee.com/MarcoMaHH/picture/raw/master/project.jpg)

---

# rent8-最常用出租屋管理系统

自家用的出租屋管理系统。本系统基于ThinkPHP8(PHP8)+TDesign(Vue3)开发，混编版前后端不分离。

[rent8-最常用出租屋管理系统-微信小程序端](https://gitee.com/MarcoMaHH/rent8_wechat)

[rent8-最常用出租屋管理系统-安装教程](https://blog.csdn.net/m0_61078449/article/details/143022025)

[rent8-最常用出租屋管理系统-使用说明](https://blog.csdn.net/m0_61078449/article/details/142572124)

### 系统功能概述

1. **房产管理**
   - 新增房产信息
   - 修改房产信息
   - 删除房产信息
2. **房间管理**
   - 新增房间信息
   - 修改房间信息
   - 删除房间信息
   - 入住管理
   - 退房管理
   - 闲置天数记录
3. **账单管理**
   - 未收账单处理：
     - 修改账单
     - 抄表（包括集中抄电表、集中抄水表）
     - 收据单生成
     - 到账确认
     - 延期处理
   - 到账账单展示
     - 账单详情查看
     - 今日到账金额统计
4. **租客档案管理**
   - 修改租客信息
   - 上传租客证件照片
5. **合同管理**
   - 修改合同内容
   - 下载合同文件
   - 合同剩余天数查看
6. **其他支出管理**
   - 维系费记录
   - 工资等支出记录
7. **水电费账单管理**
   - 水费账单：
     - 记录总水表金额及用量
     - 计算差价及差额
   - 电费账单：
     - 记录总电表金额及用量
     - 计算差价及差额
8. **房产报表生成**
   - 本月收入、支出、利润统计
   - 年度财务收支走势分析

### 界面展示

![登录页面](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/login.jpg)

![主页面](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/index.jpg)

![房号管理-页面](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/number.jpg)

![租聘合同-展示](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/contract.jpg)

![未收账单-页面](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/uncollect.jpg)

![收据单-展示](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/rent.jpg)

![到账账单-页面](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/collect.jpg)

![租客档案-页面](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/tenant.jpg)

![电费账单-页面](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/electricity.jpg)

![用户管理-页面](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/user.jpg)

### 系统环境

- PHP = 8.1.22 （PS:需要安装fileinfo扩展）
- Apache = 2.4.41
- MySQL = 5.7.28

### 技术栈

- ThinkPHP8（PHP8）
- TDesign（Vue3）
- antV G2
- printJS

### 安装步骤

1. 建立数据库`rent`
2. `git clone https://gitee.com/MarcoMaHH/rent8.git`
3. `cd rent8`
4. 将.example.env改为.env，并修改其中的数据库用户名和密码,如果使用微信小程序，则还需要填写appID和appSecret。
5. `composer install`
6. `php think migrate:run`
7. `php think seed:run`

PS:宝塔面板安装的，需要在禁用函数里移除putenv()、pcntl_signal()、proc_open()这3个函数。

### 原始账号密码

账号：admin  密码：123456

账号：user     密码：123456
