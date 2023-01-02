-- ssd.ssd_user definition

CREATE TABLE `ssd_elaina_user` (
  `user_id` int unsigned NOT NULL AUTO_INCREMENT,
  `klei_id` char(12) DEFAULT NULL,
  `net_id` char(25) NOT NULL,
  `game_platform` char(6) DEFAULT NULL,
  `register_name` varchar(100) DEFAULT NULL,
  `current_name` varchar(100) DEFAULT NULL,
  `register_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `login_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `login_ipaddress` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`net_id`),
  UNIQUE KEY `ssd_elaina_user_UN` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=100001 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TRIGGER initdata
AFTER INSERT
ON ssd_elaina_user FOR EACH ROW
begin 
	if (select count(net_id) from ssd_elaina_time  where net_id = new.net_id) = 0 then 
		insert into ssd_elaina_time(`klei_id`,`net_id`) values (new.klei_id,new.net_id);
	end if;
end