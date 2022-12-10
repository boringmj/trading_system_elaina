<?php

namespace app\index\controller;

use base\Controller;
use AdminService\Config;
use AdminService\model\Money as MoneyModel;

use function AdminService\common\json;

class Money extends Controller {

    public function transfer() {
        // 获取参数
        $from=$this->param('from','');
        $to=$this->param('to','');
        $money=$this->param('money',0);
        $remark=$this->param('remark','');
        $sign=$this->param('sign','');
        try {
            // 金额保留两位小数
            $money=round($money,2);
            if($money<=0)
                return json(-1,'金额不合法');
            // 验证签名
            $token=Config::get('app.config.all.user.key');
            $sign_array=array(
                'from'=>$from,
                'to'=>$to,
                'money'=>$money,
                'remark'=>$remark,
                'token'=>$token
            );
            $server_sign=\AdminService\common\sign($sign_array);
            if($server_sign!=$sign)
                return json(-1,'签名不合法');
            // 实例化模型
            $Money=new MoneyModel();
            // 转账
            $Money->transferByUuid($to,$from,$money,$remark);
        } catch(\AdminService\Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

}

?>