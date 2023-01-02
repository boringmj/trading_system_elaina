-- ssd.ssd_time definition

CREATE TABLE `ssd_elaina_time` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `klei_id` char(12) DEFAULT NULL,
  `net_id` char(25) DEFAULT NULL,
  `game_time` int DEFAULT NULL,
  `necklace_time` int DEFAULT NULL,
  `record_time` varchar(100) DEFAULT NULL,
  `create_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ssd_time_FK` (`net_id`),
  CONSTRAINT `ssd_time_FK` FOREIGN KEY (`net_id`) REFERENCES `ssd_elaina_user` (`net_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;