--
-- 表的结构 `trading_system_elaina_user`
--

CREATE TABLE IF NOT EXISTS `trading_system_elaina_user` (
  `id` int unsigned NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `username` varchar(36) NOT NULL,
  `password` varchar(32) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '1',
  `create_time` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `trading_system_elaina_user`
--
ALTER TABLE `trading_system_elaina_user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `trading_system_elaina_user`
--
ALTER TABLE `trading_system_elaina_user`
  MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT;