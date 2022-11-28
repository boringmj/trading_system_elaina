--
-- 表的结构 `trading_system_elaina_playing_cards_room`
--

CREATE TABLE IF NOT EXISTS `trading_system_elaina_playing_cards_room` (
  `id` int unsigned NOT NULL,
  `rmid` varchar(36) NOT NULL,
  `max_player` int(1) NOT NULL DEFAULT '4',
  `vacancy` int(1) NOT NULL DEFAULT '4',
  `status` int(1) NOT NULL DEFAULT '0',
  `playings` int(1) NOT NULL DEFAULT '0',
  `timeouts` int(10) NOT NULL DEFAULT '0',
  `public` int(1) NOT NULL DEFAULT '1',
  `create_time` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `trading_system_elaina_playing_cards_room`
--
ALTER TABLE `trading_system_elaina_playing_cards_room`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `trading_system_elaina_playing_cards_room`
--
ALTER TABLE `trading_system_elaina_playing_cards_room`
  MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT;