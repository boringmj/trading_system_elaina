--
-- 表的结构 `trading_system_elaina_user`
--

CREATE TABLE IF NOT EXISTS `ssd_user` (
  `id` int unsigned NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `username` varchar(36) NOT NULL,
  `password` varchar(32) NOT NULL,
  `nickname` varchar(36) NOT NULL DEFAULT '',
  `status` int(1) NOT NULL DEFAULT '1',
  `money` float(10,2) NOT NULL DEFAULT '0',
  `event_money` float NOT NULL DEFAULT '0',
  `qq` varchar(20) NOT NULL DEFAULT '',
  `create_time` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `trading_system_elaina_user`
--
ALTER TABLE `ssd_user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `trading_system_elaina_user`
--
ALTER TABLE `ssd_user`
  MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT;