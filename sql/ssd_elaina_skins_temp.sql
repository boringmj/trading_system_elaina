-- ssd.ssd_elaina_skins definition

CREATE TABLE `ssd_elaina_skins_temp` (
  `skinprefab` varchar(100) NOT NULL,
  `skinname` varchar(100) DEFAULT NULL,
  `klei_id` char(12) NOT NULL,
  `type` tinyint NOT NULL DEFAULT 0,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `expire_time` datetime,
  PRIMARY KEY (`skinprefab`,`klei_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;