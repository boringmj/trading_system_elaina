-- ssd.ssd_token definition

CREATE TABLE `ssd_elaina_token` (
  `klei_id` char(12) DEFAULT NULL,
  `net_id` char(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `expire_time` int DEFAULT NULL,
  `effect_time` datetime DEFAULT NULL,
  PRIMARY KEY (`net_id`) USING BTREE,
  CONSTRAINT `ssd_elaina_token.net_id` FOREIGN KEY (`net_id`) REFERENCES `ssd_elaina_user` (`net_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;