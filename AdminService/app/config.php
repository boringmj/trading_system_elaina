<?php

namespace AdminService\app;

return array(
    'user'=>array(
        'salt'=>'#m$Wtr9l2*LPX@*b7',
        'check'=>true, // 是否检查用户真实性(这将会导致性能有所下降) (default: false)
        'token'=>array(
            'expire'=>3600, // 令牌过期时间(秒) (default: 3600)
            'check'=>true, // 是否强制检查无意义令牌生成(这将会导致性能有所下降) (default: false)
            'allow_multiple'=>true, // 是否允一个用户存在多个令牌 (default: false)
            'allow_renew'=>true, // 是否允许续签(更新)令牌 (default: false)
        ),
        'register'=>array(
            'rule'=>array(
                'username'=>'/^[a-zA-Z0-9_]{4,36}$/', // 用户名规则
                'password'=>'/^.{6,36}$/' // 密码规则
            ),
            'bind_token'=>'dGwCERvJ1zis&!-JdxZww#A7EI@jS3op' // 绑定用户使用的令牌
        )
    )
);

?>