-- --------------------------------------------------------
-- 主机:                           127.0.0.1
-- 服务器版本:                        8.4.3 - MySQL Community Server - GPL
-- 服务器操作系统:                      Win64
-- HeidiSQL 版本:                  12.10.0.7000
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- 导出 rent82 的数据库结构
CREATE DATABASE IF NOT EXISTS `rent8` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `rent8`;

-- 导出  表 rent82.rent_admin_menu 结构
CREATE TABLE IF NOT EXISTS `rent_admin_menu` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int NOT NULL DEFAULT '0' COMMENT '上级id',
  `name` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `icon` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '图标',
  `controller` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '控制器',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序值',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_admin_menu 的数据：~22 rows (大约)
REPLACE INTO `rent_admin_menu` (`id`, `pid`, `name`, `icon`, `controller`, `sort`, `create_time`, `update_time`) VALUES
	(1, 0, '设置', 'system-setting', 'admin', 9, '2025-08-21 09:43:38', NULL),
	(2, 1, '菜单管理', '', 'admin.menu', 1, '2025-08-21 09:43:38', NULL),
	(3, 1, '角色管理', '', 'admin.role', 2, '2025-08-21 09:43:38', NULL),
	(4, 1, '权限管理', '', 'admin.permission', 3, '2025-08-21 09:43:38', NULL),
	(5, 1, '用户管理', '', 'admin.user', 4, '2025-08-21 09:43:38', NULL),
	(10, 0, '房屋管理', 'houses', 'house', 1, '2025-08-21 09:43:38', NULL),
	(11, 10, '房产管理', '', 'house.property', 1, '2025-08-21 09:43:38', NULL),
	(12, 10, '房间管理', '', 'house.number', 2, '2025-08-21 09:43:38', NULL),
	(13, 10, '未收账单', '', 'house.uncollected', 4, '2025-08-21 09:43:38', NULL),
	(14, 10, '到账账单', '', 'house.collected', 5, '2025-08-21 09:43:38', NULL),
	(15, 10, '其他支出', '', 'house.other', 8, '2025-08-21 09:43:38', NULL),
	(20, 0, '租客管理', 'user-business', 'tenant', 2, '2025-08-21 09:43:38', NULL),
	(21, 20, '租客信息', '', 'tenant.message', 1, '2025-08-21 09:43:38', NULL),
	(22, 20, '合同管理', '', 'tenant.contract', 2, '2025-08-21 09:43:38', NULL),
	(23, 20, '维修申请', '', 'tenant.maintain', 3, '2025-08-21 09:43:38', NULL),
	(30, 0, '水电管理', 'bill', 'we', 3, '2025-08-21 09:43:38', NULL),
	(31, 30, '水电总表', '', 'we.meter', 1, '2025-08-21 09:43:38', NULL),
	(32, 30, '水费账单', '', 'we.water', 2, '2025-08-21 09:43:38', NULL),
	(33, 30, '电费账单', '', 'we.electricity', 3, '2025-08-21 09:43:38', NULL),
	(40, 0, '我的账本', 'chart-line-data-1', 'bill', 4, '2025-08-21 09:43:38', NULL),
	(41, 40, '房产报表', '', 'bill.report', 1, '2025-08-21 09:43:38', NULL),
	(42, 40, '年度报表', '', 'bill.annual', 2, '2025-08-21 09:43:38', NULL);

-- 导出  表 rent82.rent_admin_parameter 结构
CREATE TABLE IF NOT EXISTS `rent_admin_parameter` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT '名称',
  `label` varchar(16) COLLATE utf8mb4_general_ci NOT NULL COMMENT '标签',
  `value` varchar(16) COLLATE utf8mb4_general_ci NOT NULL COMMENT '值',
  `note` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '备注',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_admin_parameter 的数据：~6 rows (大约)
REPLACE INTO `rent_admin_parameter` (`id`, `name`, `label`, `value`, `note`, `create_time`, `update_time`) VALUES
	(1, 'ymd', '年', 'year', NULL, '2025-08-21 09:43:38', NULL),
	(2, 'ymd', '月', 'month', NULL, '2025-08-21 09:43:38', NULL),
	(3, 'ymd', '日', 'day', NULL, '2025-08-21 09:43:38', NULL),
	(11, 'bill_type', '房租账单', 'A', 'primary', '2025-08-21 09:43:38', NULL),
	(12, 'bill_type', '逾期账单', 'B', 'danger', '2025-08-21 09:43:38', NULL),
	(13, 'bill_type', '退房账单', 'C', 'default', '2025-08-21 09:43:38', NULL);

-- 导出  表 rent82.rent_admin_permission 结构
CREATE TABLE IF NOT EXISTS `rent_admin_permission` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `admin_role_id` int NOT NULL DEFAULT '0' COMMENT '角色id',
  `controller` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '控制器',
  `action` varchar(256) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_admin_permission 的数据：~31 rows (大约)
REPLACE INTO `rent_admin_permission` (`id`, `admin_role_id`, `controller`, `action`, `create_time`, `update_time`) VALUES
	(1, 1, '*', '*', '2025-08-21 09:43:38', NULL),
	(10, 2, 'index', '*', '2025-08-21 09:43:38', NULL),
	(11, 2, 'house', 'index', '2025-08-21 09:43:38', NULL),
	(12, 2, 'house.property', '*', '2025-08-21 09:43:38', NULL),
	(13, 2, 'house.number', '*', '2025-08-21 09:43:38', NULL),
	(14, 2, 'house.uncollected', '*', '2025-08-21 09:43:38', NULL),
	(15, 2, 'house.collected', '*', '2025-08-21 09:43:38', NULL),
	(16, 2, 'house.other', '*', '2025-08-21 09:43:38', NULL),
	(17, 2, 'house.fee', '*', '2025-08-21 09:43:38', NULL),
	(18, 2, 'house.receipt', '*', '2025-08-21 09:43:38', NULL),
	(20, 2, 'tenant', '*', '2025-08-21 09:43:38', NULL),
	(21, 2, 'tenant.message', '*', '2025-08-21 09:43:38', NULL),
	(22, 2, 'tenant.contract', '*', '2025-08-21 09:43:38', NULL),
	(23, 2, 'tenant.maintain', '*', '2025-08-21 09:43:38', NULL),
	(30, 2, 'we', 'index', '2025-08-21 09:43:38', NULL),
	(31, 2, 'we.meter', '*', '2025-08-21 09:43:38', NULL),
	(32, 2, 'we.water', '*', '2025-08-21 09:43:38', NULL),
	(33, 2, 'we.electricity', '*', '2025-08-21 09:43:38', NULL),
	(40, 2, 'bill', 'index', '2025-08-21 09:43:38', NULL),
	(41, 2, 'bill.report', '*', '2025-08-21 09:43:38', NULL),
	(42, 2, 'bill.annual', '*', '2025-08-21 09:43:38', NULL),
	(50, 2, 'admin.parameter', '*', '2025-08-21 09:43:38', NULL),
	(60, 2, 'property', '*', '2025-08-21 09:43:38', NULL),
	(61, 2, 'number', '*', '2025-08-21 09:43:38', NULL),
	(62, 2, 'uncollected', '*', '2025-08-21 09:43:38', NULL),
	(63, 2, 'collected', '*', '2025-08-21 09:43:38', NULL),
	(64, 2, 'contract', '*', '2025-08-21 09:43:38', NULL),
	(65, 2, 'tenant', '*', '2025-08-21 09:43:38', NULL),
	(66, 2, 'report', '*', '2025-08-21 09:43:38', NULL),
	(67, 2, 'user', '*', '2025-08-21 09:43:38', NULL),
	(68, 2, 'fee', '*', '2025-08-21 09:43:38', NULL);

-- 导出  表 rent82.rent_admin_role 结构
CREATE TABLE IF NOT EXISTS `rent_admin_role` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `state` varchar(2) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y' COMMENT '状态',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_admin_role 的数据：~2 rows (大约)
REPLACE INTO `rent_admin_role` (`id`, `name`, `state`, `create_time`, `update_time`) VALUES
	(1, '管理员', 'Y', '2025-08-21 09:43:38', NULL),
	(2, '普通会员', 'Y', '2025-08-21 09:43:38', NULL);

-- 导出  表 rent82.rent_admin_subscribe 结构
CREATE TABLE IF NOT EXISTS `rent_admin_subscribe` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `open_id` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT '微信小程序openID',
  `thing1` varchar(32) COLLATE utf8mb4_general_ci NOT NULL COMMENT '房产名称',
  `time2` timestamp NOT NULL COMMENT '收租日期',
  `amount4` int NOT NULL COMMENT '应收租金',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_admin_subscribe 的数据：~0 rows (大约)

-- 导出  表 rent82.rent_admin_user 结构
CREATE TABLE IF NOT EXISTS `rent_admin_user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `admin_role_id` int NOT NULL DEFAULT '0' COMMENT '角色id',
  `username` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '密码',
  `salt` char(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '密码salt',
  `openid` varchar(32) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '微信小程序openID',
  `expiration_date` timestamp NULL DEFAULT NULL COMMENT '失效期',
  `login_date` timestamp NULL DEFAULT NULL COMMENT '最近一次登录日期',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_admin_user 的数据：~2 rows (大约)
REPLACE INTO `rent_admin_user` (`id`, `admin_role_id`, `username`, `password`, `salt`, `openid`, `expiration_date`, `login_date`, `create_time`, `update_time`) VALUES
	(1, 1, 'admin', '64430767cbd421fb2a2770fb99b1b355', 'b2548d223ac61417a7e3bae2a18c7ae4', '', '2026-08-21 09:43:38', NULL, '2025-08-21 09:43:38', NULL),
	(2, 2, 'user', '64430767cbd421fb2a2770fb99b1b355', 'b2548d223ac61417a7e3bae2a18c7ae4', '', '2026-08-21 09:43:38', NULL, '2025-08-21 09:43:38', NULL);

-- 导出  表 rent82.rent_bill_sum 结构
CREATE TABLE IF NOT EXISTS `rent_bill_sum` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `admin_user_id` int NOT NULL DEFAULT '0' COMMENT '用户id',
  `house_property_id` int NOT NULL DEFAULT '0' COMMENT '房产id',
  `type` varchar(2) COLLATE utf8mb4_general_ci NOT NULL COMMENT '类型',
  `amount` float NOT NULL DEFAULT '0' COMMENT '金额',
  `accounting_date` varchar(10) COLLATE utf8mb4_general_ci NOT NULL COMMENT '到账日期',
  `annual` varchar(6) COLLATE utf8mb4_general_ci NOT NULL COMMENT '年度',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_bill_sum 的数据：~0 rows (大约)

-- 导出  表 rent82.rent_house_fee 结构
CREATE TABLE IF NOT EXISTS `rent_house_fee` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `admin_user_id` int NOT NULL DEFAULT '0' COMMENT '用户id',
  `column_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL COMMENT '栏位id',
  `column_name` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '栏位名称',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_house_fee 的数据：~0 rows (大约)

-- 导出  表 rent82.rent_house_number 结构
CREATE TABLE IF NOT EXISTS `rent_house_number` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `house_property_id` int NOT NULL DEFAULT '0' COMMENT '房产id',
  `tenant_message_id` int DEFAULT NULL COMMENT '租客id',
  `house_receipt_id` int DEFAULT NULL COMMENT '当前收据单号',
  `name` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '房号名',
  `rental` int NOT NULL DEFAULT '0' COMMENT '租金',
  `deposit` int NOT NULL DEFAULT '0' COMMENT '押金',
  `daily_rent` int NOT NULL DEFAULT '0' COMMENT '逾期日租金',
  `checkin_date` timestamp NULL DEFAULT NULL COMMENT '入住日期',
  `payment_date` timestamp NULL DEFAULT NULL COMMENT '收款日期',
  `cycle` int NOT NULL DEFAULT '1' COMMENT '周期',
  `cycle_type` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'month' COMMENT '周期类型',
  `calculate_date` timestamp NOT NULL COMMENT '计算日期',
  `lease` int NOT NULL DEFAULT '0' COMMENT '计算期数',
  `water_price` float NOT NULL DEFAULT '0' COMMENT '水费单价',
  `electricity_price` float NOT NULL DEFAULT '0' COMMENT '电费单价',
  `fees1` int NOT NULL DEFAULT '0' COMMENT '费用1',
  `fees2` int NOT NULL DEFAULT '0' COMMENT '费用2',
  `fees3` int NOT NULL DEFAULT '0' COMMENT '费用3',
  `fees4` float NOT NULL DEFAULT '0' COMMENT '费用4',
  `equipment` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '房间设备',
  `delete_time` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_house_number 的数据：~0 rows (大约)

-- 导出  表 rent82.rent_house_other 结构
CREATE TABLE IF NOT EXISTS `rent_house_other` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `house_property_id` int NOT NULL DEFAULT '0' COMMENT '房产id',
  `type` varchar(4) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '类型',
  `total_money` float NOT NULL DEFAULT '0' COMMENT '金额',
  `note` varchar(225) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '备注',
  `accout_mark` varchar(4) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N' COMMENT '是否到账',
  `circulate_mark` varchar(4) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N' COMMENT '是否重复',
  `accounting_date` timestamp NULL DEFAULT NULL COMMENT '到账时间',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_house_other 的数据：~0 rows (大约)

-- 导出  表 rent82.rent_house_property 结构
CREATE TABLE IF NOT EXISTS `rent_house_property` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `admin_user_id` int NOT NULL DEFAULT '0' COMMENT '用户id',
  `name` varchar(8) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '房产名',
  `name_show` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '房产名(展示)',
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '详细地址',
  `landlord` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '房东id',
  `phone` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '房东手机',
  `id_card` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '房东身份证',
  `default` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N' COMMENT '是否默认',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_house_property 的数据：~1 rows (大约)

-- 导出  表 rent82.rent_house_receipt 结构
CREATE TABLE IF NOT EXISTS `rent_house_receipt` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `bill_type` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '账单类型',
  `house_property_id` int NOT NULL DEFAULT '0' COMMENT '房产id',
  `house_number_id` int NOT NULL DEFAULT '0' COMMENT '房号id',
  `tenant_message_id` int NOT NULL DEFAULT '0' COMMENT '租客id',
  `start_time` timestamp NULL DEFAULT NULL COMMENT '开始时间',
  `end_time` timestamp NULL DEFAULT NULL COMMENT '结束时间',
  `meter_reading_time` timestamp NULL DEFAULT NULL COMMENT '抄表时间',
  `electricity_meter_this_month` int DEFAULT NULL COMMENT '本月电表度数',
  `electricity_meter_last_month` int DEFAULT NULL COMMENT '上月电表度数',
  `electricity_consumption` int DEFAULT NULL COMMENT '电用量',
  `electricity` float NOT NULL DEFAULT '0' COMMENT '电费',
  `water_meter_this_month` int DEFAULT NULL COMMENT '本月水表度数',
  `water_meter_last_month` int DEFAULT NULL COMMENT '上月水表度数',
  `water_consumption` int DEFAULT NULL COMMENT '水用量',
  `water` float NOT NULL DEFAULT '0' COMMENT '水费',
  `rental` int NOT NULL DEFAULT '0' COMMENT '租金',
  `deposit` int NOT NULL DEFAULT '0' COMMENT '押金',
  `fees1` int NOT NULL DEFAULT '0' COMMENT '费用1',
  `fees2` float NOT NULL DEFAULT '0' COMMENT '费用2',
  `fees3` float NOT NULL DEFAULT '0' COMMENT '费用3',
  `fees4` float NOT NULL DEFAULT '0' COMMENT '费用4',
  `total_money` double NOT NULL DEFAULT '0' COMMENT '总金额',
  `note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '备注',
  `accounting_date` timestamp NULL DEFAULT NULL COMMENT '到账时间',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_house_receipt 的数据：~0 rows (大约)

-- 导出  表 rent82.rent_migrations 结构
CREATE TABLE IF NOT EXISTS `rent_migrations` (
  `version` bigint NOT NULL,
  `migration_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 正在导出表  rent82.rent_migrations 的数据：~20 rows (大约)
REPLACE INTO `rent_migrations` (`version`, `migration_name`, `start_time`, `end_time`, `breakpoint`) VALUES
	(20220424100409, 'AdminUser', '2025-08-21 09:43:36', '2025-08-21 09:43:36', 0),
	(20220501133350, 'AdminMenu', '2025-08-21 09:43:36', '2025-08-21 09:43:36', 0),
	(20220512015436, 'AdminRole', '2025-08-21 09:43:36', '2025-08-21 09:43:36', 0),
	(20220512095231, 'AdminPermission', '2025-08-21 09:43:36', '2025-08-21 09:43:36', 0),
	(20220515051037, 'HouseProperty', '2025-08-21 09:43:36', '2025-08-21 09:43:36', 0),
	(20220515110609, 'HouseNumber', '2025-08-21 09:43:36', '2025-08-21 09:43:36', 0),
	(20220723120341, 'BillSum', '2025-08-21 09:43:36', '2025-08-21 09:43:36', 0),
	(20240904025452, 'HouseOther', '2025-08-21 09:43:36', '2025-08-21 09:43:36', 0),
	(20240904031220, 'WeMeter', '2025-08-21 09:43:36', '2025-08-21 09:43:36', 0),
	(20240904031317, 'WeBill', '2025-08-21 09:43:36', '2025-08-21 09:43:36', 0),
	(20240904094107, 'WeDetail', '2025-08-21 09:43:36', '2025-08-21 09:43:36', 0),
	(20250319125728, 'AdminSubscribe', '2025-08-21 09:43:36', '2025-08-21 09:43:36', 0),
	(20250331132931, 'PhotoContract', '2025-08-21 09:43:36', '2025-08-21 09:43:36', 0),
	(20250331133206, 'PhotoTenant', '2025-08-21 09:43:36', '2025-08-21 09:43:36', 0),
	(20250401063433, 'AdminParameter', '2025-08-21 09:43:36', '2025-08-21 09:43:36', 0),
	(20250406125310, 'HouseReceipt', '2025-08-21 09:43:36', '2025-08-21 09:43:36', 0),
	(20250410014018, 'HouseFee', '2025-08-21 09:43:36', '2025-08-21 09:43:37', 0),
	(20250510011608, 'TenantMessage', '2025-08-21 09:43:37', '2025-08-21 09:43:37', 0),
	(20250510011807, 'TenantContract', '2025-08-21 09:43:37', '2025-08-21 09:43:37', 0),
	(20250510012139, 'TenantMaintain', '2025-08-21 09:43:37', '2025-08-21 09:43:37', 0);

-- 导出  表 rent82.rent_photo_contract 结构
CREATE TABLE IF NOT EXISTS `rent_photo_contract` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `house_property_id` int NOT NULL DEFAULT '0' COMMENT '房产id',
  `tenant_contract_id` int NOT NULL DEFAULT '0' COMMENT '合同id',
  `url` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '照片路径',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_photo_contract 的数据：~0 rows (大约)

-- 导出  表 rent82.rent_photo_tenant 结构
CREATE TABLE IF NOT EXISTS `rent_photo_tenant` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `house_property_id` int NOT NULL DEFAULT '0' COMMENT '房产id',
  `tenant_message_id` int NOT NULL DEFAULT '0' COMMENT '租客id',
  `url` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '照片路径',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_photo_tenant 的数据：~0 rows (大约)

-- 导出  表 rent82.rent_tenant_contract 结构
CREATE TABLE IF NOT EXISTS `rent_tenant_contract` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `house_property_id` int NOT NULL DEFAULT '0' COMMENT '房产id',
  `house_number_id` int NOT NULL DEFAULT '0' COMMENT '房间id',
  `tenant_message_id` int NOT NULL DEFAULT '0' COMMENT '租客id',
  `type` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y' COMMENT '合同类型',
  `start_date` timestamp NULL DEFAULT NULL COMMENT '开始日期',
  `end_date` timestamp NULL DEFAULT NULL COMMENT '结束日期',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_tenant_contract 的数据：~0 rows (大约)

-- 导出  表 rent82.rent_tenant_maintain 结构
CREATE TABLE IF NOT EXISTS `rent_tenant_maintain` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `house_property_id` int NOT NULL DEFAULT '0' COMMENT '房产id',
  `house_number_id` int NOT NULL DEFAULT '0' COMMENT '房间id',
  `status` varchar(4) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N' COMMENT '状态(Y:已解决,N:未解决)',
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '说明',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_tenant_maintain 的数据：~0 rows (大约)

-- 导出  表 rent82.rent_tenant_message 结构
CREATE TABLE IF NOT EXISTS `rent_tenant_message` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `house_property_id` int NOT NULL DEFAULT '0' COMMENT '房产id',
  `house_number_id` int NOT NULL DEFAULT '0' COMMENT '房号id',
  `pid` int NOT NULL DEFAULT '0' COMMENT '上级id',
  `name` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '姓名',
  `sex` varchar(4) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '性别',
  `checkout_mark` varchar(4) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N' COMMENT '退房标注',
  `phone` varchar(11) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '电话号码',
  `id_card_number` varchar(18) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '身份证号码',
  `native_place` varchar(32) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '籍贯',
  `work_units` varchar(225) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '工作单位',
  `note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '备注',
  `checkin_date` timestamp NULL DEFAULT NULL COMMENT '入住日期',
  `leave_date` timestamp NULL DEFAULT NULL COMMENT '离开日期',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_tenant_message 的数据：~0 rows (大约)

-- 导出  表 rent82.rent_we_bill 结构
CREATE TABLE IF NOT EXISTS `rent_we_bill` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `meter_id` int NOT NULL DEFAULT '0' COMMENT '总表id',
  `house_property_id` int NOT NULL DEFAULT '0' COMMENT '房产id',
  `accounting_date` timestamp NULL DEFAULT NULL COMMENT '到账日期',
  `start_month` timestamp NULL DEFAULT NULL COMMENT '开始月份',
  `end_month` timestamp NULL DEFAULT NULL COMMENT '结束月份',
  `master_dosage` int NOT NULL DEFAULT '0' COMMENT '总表用量',
  `master_sum` float NOT NULL DEFAULT '0' COMMENT '总表金额',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_we_bill 的数据：~0 rows (大约)

-- 导出  表 rent82.rent_we_detail 结构
CREATE TABLE IF NOT EXISTS `rent_we_detail` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `meter_id` int NOT NULL DEFAULT '0' COMMENT '总表id',
  `house_property_id` int NOT NULL DEFAULT '0' COMMENT '房产id',
  `type` varchar(2) COLLATE utf8mb4_general_ci NOT NULL COMMENT '类型：B电费,C水费',
  `amount` float NOT NULL DEFAULT '0' COMMENT '金额',
  `dosage` float NOT NULL DEFAULT '0' COMMENT '用量',
  `calculate_date` timestamp NULL DEFAULT NULL COMMENT '计算日期',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_we_detail 的数据：~0 rows (大约)

-- 导出  表 rent82.rent_we_meter 结构
CREATE TABLE IF NOT EXISTS `rent_we_meter` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `type` varchar(4) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '类型',
  `house_property_id` int NOT NULL DEFAULT '0' COMMENT '房产id',
  `house_number_id` varchar(128) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '房号id',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 正在导出表  rent82.rent_we_meter 的数据：~0 rows (大约)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
