-- ssd.ssd_elaina_cdkey_skin definition

CREATE TABLE `ssd_elaina_cdkey_info` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cdk` char(25) DEFAULT NULL,
  `skinprefab` varchar(255) DEFAULT NULL,
  `skinname` varchar(255) DEFAULT NULL,
  `user_kid` char(12) DEFAULT NULL,
  `use_time` datetime DEFAULT NULL,
  `skin_expire_time` int DEFAULT -1,
  PRIMARY KEY (`id`),
  KEY `ssd_elaina_cdkey_skin_FK` (`cdk`),
  CONSTRAINT `ssd_elaina_cdkey_skin_FK` FOREIGN KEY (`cdk`) REFERENCES `ssd_elaina_cdkey` (`cdk`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;