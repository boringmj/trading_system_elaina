--
-- 表的结构 `trading_system_elaina_bank_award`
--

CREATE TABLE IF NOT EXISTS `ssd_bank_award` (
  `id` int unsigned NOT NULL,
  `code` varchar(36) NOT NULL,
  `min_money` float(10,2) NOT NULL DEFAULT '0.00',
  `max_money` float(10,2) NOT NULL DEFAULT '0.00',
  `money` float(10,2) NOT NULL DEFAULT '-1',
  `remark` varchar(255) NOT NULL DEFAULT '您发现了一条隐藏的链接',
  `check` int(1) NOT NULL DEFAULT '0',
  `expire_time` int(10) NOT NULL DEFAULT '2147483647'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `trading_system_elaina_bank_award`
--
ALTER TABLE `ssd_bank_award`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `trading_system_elaina_bank_award`
--
ALTER TABLE `ssd_bank_award`
  MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT;