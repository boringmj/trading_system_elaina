<?php

namespace AdminService\common;

/**
 * 获取ip地址
 * 
 * @return string
 */
function ipaddress(): string
{
  if (getenv("HTTP_CLIENT_IP"))
    $ip = getenv("HTTP_CLIENT_IP");
  else if (getenv("HTTP_X_FORWARDED_FOR"))
    $ip = getenv("HTTP_X_FORWARDED_FOR");
  else if (getenv("REMOTE_ADDR"))
    $ip = getenv("REMOTE_ADDR");
  else
    $ip = "Unknown";
  return $ip;
}