--
-- 表的结构 `trading_system_elaina_bank_award_find`
--

CREATE TABLE IF NOT EXISTS `trading_system_elaina_bank_award_find` (
  `id` int unsigned NOT NULL,
  `pid` int unsigned NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `money` float(10,2) NOT NULL DEFAULT '0.00',
  `create_time` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `trading_system_elaina_bank_award_find`
--
ALTER TABLE `trading_system_elaina_bank_award_find`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `trading_system_elaina_bank_award_find`
--
ALTER TABLE `trading_system_elaina_bank_award_find`
  MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT;