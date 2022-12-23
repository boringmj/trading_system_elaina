<?php

namespace AdminService\app;

return array(
    'user'=>array(
        'salt'=>'#m$Wtr9l2*LPX@*b7', // 用户密码加密盐(在上线前允许随意修改, 请勿在上线后修改, 这将导致旧的用户无法登录)
        'check'=>true, // 是否检查用户真实性(这将会导致性能有所下降) (default: false)
        'token'=>array(
            'expire'=>86400, // 令牌过期时间(秒) (default: 3600)
            'check'=>true, // 是否强制检查无意义令牌生成(这将会导致性能有所下降) (default: false)
            'allow_multiple'=>true, // 是否允一个用户存在多个令牌 (default: false)
            'allow_renew'=>true, // 是否允许续签(更新)令牌 (default: false)
        ),
        'register'=>array(
            'rule'=>array(
                'username'=>'/^[a-zA-Z0-9_]{4,36}$/', // 用户名规则
                'password'=>'/^.{6,36}$/' // 密码规则
            )
        ),
        'key'=>'dGwCERvJ1zis&!-JdxZww#A7EI@jS3od' // 系统调用接口的密钥(请在上线前进行修改, 上线后允许自由修改)
    ),
    'mall'=>array(
        'cdkey_verify_url'=>'https://pivix.cn/dst/verifycdkey/', // CDKEY校验地址
        'cdkey_replacecdk_url'=>'https://pivix.cn/dst/replacecdk/', // CDKEY替换地址
        'cdkey_unlock_url'=>'https://pivix.cn/dst/unlockcdk/', // CDKEY解锁地址
    )
);

?>