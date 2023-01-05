CREATE TABLE ssd.ssd_elaina_skins (
	skinprefab varchar(100)not NULL,
	skinname varchar(100) NULL,
	klei_id char(12)not NULL,
	`type` tinyint not null default 0,
	create_time DATETIME DEFAULT CURRENT_TIMESTAMP NULL,
	CONSTRAINT ssd_elaina_skins_PK PRIMARY KEY (skinprefab,klei_id)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_0900_ai_ci;
