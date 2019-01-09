# A股历年来股票分红派息数据


### 表结构

```sql
CREATE TABLE `stocks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `code` varchar(20) COLLATE utf8mb4_bin NOT NULL DEFAULT '' COMMENT '股票代码',
  `name` varchar(100) COLLATE utf8mb4_bin NOT NULL DEFAULT '' COMMENT '股票名称',
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '股票当前价格',
  `roe` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '净资产收益率',
  `pb` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '市净率',
  `pe` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '市盈率',
  `listed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '上市时间',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_code` (`code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;


CREATE TABLE `bonus` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stock_code` varchar(100) COLLATE utf8mb4_bin NOT NULL DEFAULT '' COMMENT '股票代码',
  `program_desc` varchar(255) COLLATE utf8mb4_bin NOT NULL DEFAULT '' COMMENT '派息说明',
  `report_date` varchar(100) COLLATE utf8mb4_bin NOT NULL DEFAULT '' COMMENT '报告日期',
  `year` int(4) unsigned NOT NULL DEFAULT '0' COMMENT '年份',
  `meeting_date` date NOT NULL DEFAULT '1970-01-01' COMMENT '董事会日期',
  `announcement_date` date NOT NULL DEFAULT '1970-01-01' COMMENT '股东大会预案公告日期',
  `material_date` date NOT NULL DEFAULT '1970-01-01' COMMENT '实施日期',
  `stock_registration_date` date NOT NULL DEFAULT '1970-01-01' COMMENT '股权登记日',
  `ex_dividend_date` date NOT NULL DEFAULT '1970-01-01' COMMENT '除权除息日',
  `programme_progress` varchar(255) COLLATE utf8mb4_bin NOT NULL DEFAULT '' COMMENT '方案进度',
  `payout_ratio` float(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '股息支付率',
  `dividend_rate` float(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '分红率',
  `dividend_money` decimal(20,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '分红金额',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock_code` (`stock_code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='红利、派息';

```