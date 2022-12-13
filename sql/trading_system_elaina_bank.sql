--
-- 表的结构 `trading_system_elaina_bank`
--

CREATE TABLE IF NOT EXISTS `trading_system_elaina_bank` (
  `id` int unsigned NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `qq` varchar(20) DEFAULT NULL,
  `nickname` varchar(36) DEFAULT NULL,
  `money` float NOT NULL DEFAULT '0',
  `base_money` float NOT NULL DEFAULT '0',
  `wait_save_money` float NOT NULL DEFAULT '0',
  `wait_take_money` float NOT NULL DEFAULT '0',
  `save_date` int(10) DEFAULT NULL,
  `take_date` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `trading_system_elaina_bank`
--
ALTER TABLE `trading_system_elaina_bank`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `trading_system_elaina_bank`
--
ALTER TABLE `trading_system_elaina_bank`
  MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT;