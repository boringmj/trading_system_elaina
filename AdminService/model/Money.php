<?php

namespace AdminService\model;

use base\Model;
use AdminService\Exception;
use AdminService\model\User;

class Money extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='trading_system_elaina_amount_details';

    /**
     * 通过UUID为用户转入一笔交易记录
     * 
     * @access public
     * @param string $uuid 用户ID
     * @param string $from_uuid 来源用户ID
     * @param float $money 交易金额
     * @param string $remark 交易备注
     * @return void
     * @throws Exception
     */
    public function transferByUuid(string $uuid,string $from_uuid,float $money,string $remark='') {
        // 获取用户信息
        $User=new User();
        $user_money_to=$User->getMoney($uuid);
        $user_money_from=$User->getMoney($from_uuid);
        // 检查用户余额
        if($user_money_from<$money)
            throw new Exception("用户余额不足",0,array(
                'from_money'=>$user_money_from
            ));
        // 更新用户余额(事务处理)
        $User->beginTransaction();
        $this->beginTransaction();
        try {
            $User->where('uuid',$uuid)->update(array(
                'money'=>$user_money_to+$money
            ));
            $User->where('uuid',$from_uuid)->update(array(
                'money'=>$user_money_from-$money
            ));
            $this->insert(array(
                'uuid'=>$uuid,
                'from_uuid'=>$from_uuid,
                'money'=>$money,
                'remark'=>$remark,
                'create_time'=>time()
            ));
            $User->commit();
            $this->commit();
        } catch(Exception $e) {
            $User->rollback();
            $this->rollback();
            throw $e;
        }
    }

}