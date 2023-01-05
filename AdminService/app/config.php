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
    'system'=>array(
        'uuid'=>array(
            'bank'=>'63986dfe-4444-4444-4444-444444444444', // 银行UUID
            'system_user'=>'63969c0a-1111-1111-1111-111111111111'//系统用户UUID
        )
    ),
    'bank'=>array(
        'admin_uuid'=>array( // 银行管理员UUID
            '638a2954-20c9-963f-fcb7-4c757c57cf27',
            '638a2852-b5e5-50e9-f5ad-faf79a2c9b0e',
            '638a10e2-a1d4-2294-395b-7faa609b3005'
        ),
        'interest'=>array(
            'rate'=>0.001, // 利率
            'date_min'=>7, // 最小存款天数(利息按天结算)
        )
    ),
    'view'=>array(
        'list_max'=>20, // 列表最大显示数量
        'currency_name'=>'兔元', // 货币名称
    ),
    'mall'=>array(
        'cdkey_verify_url'=>'https://pivix.cn/dst/verifycdkey/', // CDKEY校验地址
        'cdkey_replacecdk_url'=>'https://pivix.cn/dst/replacecdk/', // CDKEY替换地址
        'cdkey_unlock_url'=>'https://pivix.cn/dst/unlockcdk/', // CDKEY解锁地址
        'rule'=>array(
            'price'=>array(
                'min'=>8.88, // 最低价格
                'max'=>168, // 最高价格
                'handling_fee'=>0.0888, // 银行获取的手续费
                'handling_fee_admin'=>0.4, // 管理员获取的手续费(该项是在银行获取的手续费上扣除的)
                'handling_fee_admin_max'=>2, // 管理员获取的手续费上限(该项是在银行获取的手续费上扣除的)
                'handling_fee_admin_uuid'=>'638a2852-b5e5-50e9-f5ad-faf79a2c9b0e', // 管理员UUID
            ),
        ),
        'privilege'=>array( // 特权
            'qq'=>array(
                '1258706440'=>'638a10e2-a1d4-2294-395b-7faa609b3005'
            ),
            '638a10e2-a1d4-2294-395b-7faa609b3005'=>array(
                'quick'=>true, // 是否允许快速上架
                'unlimited_price'=>true, // 是否允许无限制价格
                'admin'=>array(
                    'show'=>true, // 是否允许查看商品详细信息
                    'edit'=>true // 是否允许编辑商品信息
                )
            )
        )
    )
);

?>