--
-- 表的结构 `trading_system_elaina_user_token`
--

CREATE TABLE IF NOT EXISTS `ssd_user_token` (
  `id` int unsigned NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `token` varchar(36) NOT NULL,
  `create_time` int  NOT NULL,
  `expire_time` int  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `trading_system_elaina_user_token`
--
ALTER TABLE `ssd_user_token`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `trading_system_elaina_user_token`
--
ALTER TABLE `ssd_user_token`
  MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT;