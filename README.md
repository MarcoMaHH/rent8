# rent8-最常用出租屋管理系统

自家用的出租屋管理系统。本系统基于ThinkPHP8(PHP8)+TDesign开发，前后端**不**分离，这是**最最后的倔强**！！！

---

下面是出租屋管理系统微信小程序版，免费使用，适合白嫖党，小白党！

![最常用房东助手](https://gitee.com/MarcoMaHH/picture/raw/master/project.jpg)

---

**其他版本**：

[最常用出租屋管理系统rent2混编版-thinkph5.1+layUI](https://gitee.com/MarcoMaHH/rent2)

[最常用出租屋管理系统rent4混编版-thinkphp6+ElementUI](https://gitee.com/MarcoMaHH/rent4)

[最常用出租屋管理系统rent6前后端分离版-thinkphp8+TDesign](https://gitee.com/MarcoMaHH/rent6)

[最常用出租屋管理系统rent8混编版-thinkphp8+TDesign](https://gitee.com/MarcoMaHH/rent8)

### 系统环境

- PHP = 8.1.22

- Apache = 2.4.41

- MySQL = 5.7.28

### 技术栈

- thinkphp8
- TDesign（Vue Next for Web）
- antV G2
- printJS

### 界面及功能展示

登录页面

![登录页面](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/login.jpg)

主页面

![主页面](https://gitee.com/MarcoMaHH/rent4/raw/master/picture/index.jpg)

房号管理-页面

![房号管理-页面](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/number.jpg)

租聘合同-展示

![租聘合同-展示](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/contract.png)

未收账单-页面

![未收账单-页面](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/uncollect.jpg)

收据单-展示

![收据单-展示](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/rent.jpg)

到账账单-页面

![到账账单-页面](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/collect.jpg)

菜单管理-页面

![菜单管理-页面](https://gitee.com/MarcoMaHH/rent8/raw/master/picture/menu.jpg)

### 安装步骤

1. 建立数据库`rent8`
2. `git clone https://gitee.com/MarcoMaHH/rent8.git`
3. 将.example.env改为.env，并修改为自己的数据
4. 在根目录执行`composer install`
5. `php think migrate:run`
6. `php think seed:run`

### 原始账号密码

账号：admin  密码：123456

账号：user      密码：123456

