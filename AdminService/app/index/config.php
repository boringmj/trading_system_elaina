<?php

namespace AdminService\app\index;

return array(
    'user'=>array(
        'salt'=>'#m$Wtr9l2*LPX@*b7',
        'token'=>array(
            'expire'=>604800, // 令牌过期时间: 一周 (default: 3600)
            'check'=>true, // 是否强制检查无意义令牌生成(这将会导致性能有所下降) (default: false)
            'allow_multiple'=>false // 是否允一个用户存在多个令牌 (default: false)
        ),
        'register'=>array(
            'rule'=>array(
                'username'=>'/^[a-zA-Z0-9_]{4,16}$/', // 用户名规则
                'password'=>'/^.{6,16}$/' // 密码规则  
            ),
            'bind_token'=>'dGwCERvJ1zis&!-JdxZww#A7EI@jS3op' // 绑定用户使用的令牌
        )
    )
);

?>