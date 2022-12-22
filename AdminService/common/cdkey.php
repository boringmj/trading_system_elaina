<?php

namespace AdminService\common;

/**
 * 生成cdkey
 * 
 * @return string
 */
function cdkey(): string {
    $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $cdkey='';
    // 每次生成5位,共生成4组,每组之间用-分隔
    for($i=0;$i<4;$i++) {
        for($j=0;$j<5;$j++)
            $cdkey.=$chars[mt_rand(0,strlen($chars)-1)];
        if($i!=3)
            $cdkey.='-';
    }
    return $cdkey;
}

?>