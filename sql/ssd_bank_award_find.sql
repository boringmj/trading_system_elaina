--
-- 表的结构 `trading_system_elaina_bank_award_find`
--

CREATE TABLE IF NOT EXISTS `ssd_bank_award_find` (
  `id` int unsigned NOT NULL,
  `pid` int unsigned NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `money` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `create_time` int  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `trading_system_elaina_bank_award_find`
--
ALTER TABLE `ssd_bank_award_find`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `trading_system_elaina_bank_award_find`
--
ALTER TABLE `ssd_bank_award_find`
  MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT;