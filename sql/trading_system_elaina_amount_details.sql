--
-- 表的结构 `trading_system_elaina_amount_details`
--

CREATE TABLE IF NOT EXISTS `trading_system_elaina_amount_details` (
  `id` int unsigned NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `from_uuid` varchar(36) NOT NULL,
  `money` float(10,2) NOT NULL DEFAULT '0',
  `remark` varchar(255) NULL DEFAULT '',
  `create_time` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `trading_system_elaina_amount_details`
--
ALTER TABLE `trading_system_elaina_amount_details`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `trading_system_elaina_amount_details`
--
ALTER TABLE `trading_system_elaina_amount_details`
  MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT;