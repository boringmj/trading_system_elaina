--
-- 表的结构 `trading_system_elaina_amount_details`
--

CREATE TABLE IF NOT EXISTS `ssd_amount_details` (
  `id` int unsigned NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `from_uuid` varchar(36) NOT NULL,
  `money` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `remark` varchar(255) NULL DEFAULT '',
  `create_time` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 
-- Indexes for dumped tables
--

--
-- Indexes for table `trading_system_elaina_amount_details`
--
ALTER TABLE `ssd_amount_details`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `trading_system_elaina_amount_details`
--
ALTER TABLE `ssd_amount_details`
  MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT;