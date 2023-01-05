-- ssd.ssd_elaina_cdkey definition

CREATE TABLE `ssd_elaina_cdkey` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cdk` char(25) DEFAULT NULL,
  `value` int DEFAULT -1 COMMENT '价值,-1为正常皮肤',
  `type` tinyint DEFAULT 0 COMMENT '类型,默认0普通皮肤',
  `lock` tinyint DEFAULT 0 COMMENT '锁定,商店相关',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `cdk_expire_time` int DEFAULT 30 COMMENT 'cdk失效时间(天)',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ssd_elaina_cdkey_UN` (`cdk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;