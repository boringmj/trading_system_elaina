-- ssd.ssd_elaina_usercode definition

CREATE TABLE `ssd_elaina_usercode` (
  `klei_id` char(12) DEFAULT NULL,
  `net_id` char(25) DEFAULT NULL,
  `bindcode` char(25) DEFAULT NULL,
  `qq` char(12) NOT NULL,
  `ask` int DEFAULT 1,
  `receive` varchar(6) DEFAULT '0',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `bind_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;