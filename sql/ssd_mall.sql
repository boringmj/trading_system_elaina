--
-- 表的结构 `trading_system_elaina_mall`
--

CREATE TABLE IF NOT EXISTS `ssd_mall` (
  `id` int unsigned NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `cdkey` varchar(36) NOT NULL,
  `product_uuid` varchar(36) NOT NULL,
  `product_name` varchar(36) NOT NULL,
  `product_code` varchar(36) NOT NULL,
  `status` int NOT NULL DEFAULT 1,
  `price` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `priority` int  NULL DEFAULT 0,
  `buy_uuid` varchar(36) NULL DEFAULT '',
  `create_time` int  NOT NULL,
  `tag` varchar(254) NULL DEFAULT '',
  `update_time` int  NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `trading_system_elaina_mall`
--
ALTER TABLE `ssd_mall`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `trading_system_elaina_mall`
--
ALTER TABLE `ssd_mall`
  MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT;