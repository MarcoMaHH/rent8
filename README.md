# 最常用出租屋管理系统

自家用的出租屋管理系统。本系统基于thinphp6+elementUI开发，前后端**不**分离，这是**最后的倔强**！！！

[最常用出租屋管理系统rent4-微信小程序端](https://gitee.com/MarcoMaHH/rent4_wechat)

---

出租屋管理系统微信小程序版，免费使用，适合白嫖党！

![最常用房东助手](https://gitee.com/MarcoMaHH/picture/raw/master/project.jpg)

---

请使用Edge或Chrome浏览器，Firefox的“打印功能”体验不好。

[最常用出租屋管理系统安装记录-腾讯云宝塔版 For小小白,大神请忽略](https://blog.csdn.net/m0_61078449/article/details/131347945)

**其他版本**：

[最常用出租屋管理系统rent2混编版-thinkph5.1+layUi](https://gitee.com/MarcoMaHH/rent2)

[最常用出租屋管理系统rent4混编版-thinkphp6+elementUI](https://gitee.com/MarcoMaHH/rent4)

[最常用出租屋管理系统rent6前后端分离版-thinkphp8+TDesign](https://gitee.com/MarcoMaHH/rent6)

### 系统环境

- PHP = 7.4.28

- Apache = 2.4.41

- MySQL = 5.7.28

### 技术栈

- thinkphp6
- vue3
- element UI
- variant form 3
- antV G2
- printJS

另外，分享tp5.1升级到tp6遇到的问题。

[thinkphp5.1升级thinkphp6遇到的坑](https://blog.csdn.net/m0_61078449/article/details/126403204)

### 界面及功能展示

主页面

![index.jpg (1920×889) (gitee.com)](https://gitee.com/MarcoMaHH/rent4/raw/master/picture/index.jpg)

房号管理-页面

![number.jpg (1920×889) (gitee.com)](https://gitee.com/MarcoMaHH/rent4/raw/master/picture/number.jpg)

租聘合同-展示

![number.jpg (1920×889) (gitee.com)](https://gitee.com/MarcoMaHH/rent4/raw/master/picture/contract.png)

未收账单-页面

![uncollect.jpg (1920×889) (gitee.com)](https://gitee.com/MarcoMaHH/rent4/raw/master/picture/uncollect.jpg)

收据单-展示

![rent](https://gitee.com/MarcoMaHH/rent4/raw/master/picture/rent.jpg)

到账账单-页面

![collect.jpg (1920×889) (gitee.com)](https://gitee.com/MarcoMaHH/rent4/raw/master/picture/collect.jpg)

菜单管理-页面

![menu.jpg (1920×889) (gitee.com)](https://gitee.com/MarcoMaHH/rent4/raw/master/picture/menu.jpg)

### 安装步骤

1. 建立数据库`zcy`
2. `git clone https://gitee.com/MarcoMaHH/rent4.git`
3. 将.example.env改为.env，并修改为自己的数据
4. 在根目录执行`composer install`
5. `php think migrate:run`
6. `php think seed:run`
7. 执行SQL文件：zcy_detail_electricity.sql

### 原始账号密码

账号：admin  密码：123456

账号：user     密码：123456
