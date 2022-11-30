--
-- 表的结构 `trading_system_elaina_playing_cards_players`
--

CREATE TABLE IF NOT EXISTS `trading_system_elaina_playing_cards_players` (
  `id` int unsigned NOT NULL,
  `rmid` varchar(36) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0',
  `serial` int(1) NOT NULL DEFAULT '0',
  `cards` varchar(255) NULL DEFAULT NULL,
  `group` varchar(2) NULL DEFAULT NULL,
  `cards_count` int(2) NOT NULL DEFAULT '0',
  `cards_played` varchar(255) NULL DEFAULT NULL,
  `create_time` int(10) NOT NULL,
  `update_time` int(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `trading_system_elaina_playing_cards_players`
--
ALTER TABLE `trading_system_elaina_playing_cards_players`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `trading_system_elaina_playing_cards_players`
--
ALTER TABLE `trading_system_elaina_playing_cards_players`
  MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT;