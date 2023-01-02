--
-- 表的结构 `trading_system_elaina_playing_cards_room`
--

CREATE TABLE IF NOT EXISTS `ssd_playing_cards_room` (
  `id` int unsigned NOT NULL,
  `rmid` varchar(36) NOT NULL,
  `max_player` int NOT NULL DEFAULT 4,
  `vacancy` int NOT NULL DEFAULT 4,
  `status` int NOT NULL DEFAULT 0,
  `timeouts` int  NOT NULL DEFAULT 0,
  `public` int NOT NULL DEFAULT 1,
  `previous_player` int NOT NULL DEFAULT 0,
  `current_player` int NOT NULL DEFAULT 0,
  `ranking` varchar(9) NULL DEFAULT NULL,
  `previous_cards` varchar(255) NULL DEFAULT NULL,
  `create_time` int  NOT NULL,
  `update_time` int  NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `trading_system_elaina_playing_cards_room`
--
ALTER TABLE `ssd_playing_cards_room`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `trading_system_elaina_playing_cards_room`
--
ALTER TABLE `ssd_playing_cards_room`
  MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT;