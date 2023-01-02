--
-- 表的结构 `ssd_bank`
--

CREATE TABLE IF NOT EXISTS `ssd_bank` (
  `id` int unsigned NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `qq` varchar(20) DEFAULT NULL,
  `nickname` varchar(36) DEFAULT NULL,
  `money` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `base_money` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `wait_save_money` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `wait_take_money` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `save_date` int  DEFAULT NULL,
  `take_date` int  DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ssd_bank`
--
ALTER TABLE `ssd_bank`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ssd_bank`
--
ALTER TABLE `ssd_bank`
  MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT;