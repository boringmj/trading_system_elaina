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
            return json(1,'转账成功');
        } catch(\AdminService\Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

    public function transfer_qq() {
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
            $Money->transferByQq($to,$from,$money,$remark);
            return json(1,'转账成功');
        } catch(\AdminService\Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

    public function rollback() {
        //return json(-1,'error');
        $Money=new MoneyModel();
        $date="2022-12-12 00:00:00";
        $timestamp=strtotime($date);
        //$Money->rollbackByFromUuid('63969c0a-c237-3031-707e-98e8791e1111',$timestamp);
        $Money->rollbackByRemark('余额回滚',$timestamp,'取消回滚');
        $Money->rollbackByRemark('存钱',$timestamp,'存款回滚');
        $Money->rollbackByRemark('取钱',$timestamp,'取款回滚');
        return json(1,'success');
    }

}

?>