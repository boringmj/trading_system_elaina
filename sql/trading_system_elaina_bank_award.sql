--
-- 表的结构 `trading_system_elaina_bank_award`
--

CREATE TABLE IF NOT EXISTS `trading_system_elaina_bank_award` (
  `id` int unsigned NOT NULL,
  `code` varchar(36) NOT NULL,
  `min_money` float(10,2) NOT NULL DEFAULT '0.00',
  `max_money` float(10,2) NOT NULL DEFAULT '0.00',
  `remark` varchar(255) NOT NULL,
  `expire_time` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `trading_system_elaina_bank_award`
--
ALTER TABLE `trading_system_elaina_bank_award`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `trading_system_elaina_bank_award`
--
ALTER TABLE `trading_system_elaina_bank_award`
  MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT;