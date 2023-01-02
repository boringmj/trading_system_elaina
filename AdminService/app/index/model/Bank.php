<?php

namespace app\index\model;

use base\Model;
use AdminService\Config;
use AdminService\Exception;
use AdminService\model\User;
use AdminService\model\Token;

class Bank extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='ssd_bank';

    /**
     * 获取用户银行信息
     * 
     * @access public
     * @param string $uuid 用户UUID
     * @return array
     * @throws Exception
     */
    public function getBankInfoByUuid(string $uuid): array {
        // 检查用户真实性(防止伪造用户uuid)
        if(Config::get('app.config.all.user.check',false)) {
            $User=new User();
            if(empty($User->getUserInfoByUUID($uuid)))
                throw new Exception("用户不存在");
        }
        // 获取用户银行信息
        $bank_info=$this->where('uuid',$uuid)->find();
        if(empty($bank_info))
            throw new Exception("用户银行信息不存在");
        return $bank_info;
    }

    /**
     * 获取所有银行信息
     * !危险警告,该方法会被sql注入攻击
     * 
     * @access public
     * @param string $filter 过滤条件
     * @return array
     * @throws Exception
     */
    public function getAllBankInfo(string $filter='1=1'): array {
        // 构造查询语句
        $sql="
            SELECT
            `user`.`uuid`,`user`.`qq`,`user`.`nickname`,`user`.`money`,
            `bank`.`money`as`bank_money`,`bank`.`base_money`,`bank`.`wait_save_money`,`bank`.`wait_take_money`,
            `bank`.`save_date`,`bank`.`take_date`
            FROM `{$this->table_name}` `bank`
            LEFT JOIN `trading_system_elaina_user` `user` ON `bank`.`uuid`=`user`.`uuid`
            WHERE {$filter}
        ";
        try {
            $db=$this->getDb();
            // 执行查询
            $stm=$db->prepare($sql);
            $stm->execute();
            // 只需要键名
            $bank_info=$stm->fetchAll(\PDO::FETCH_ASSOC);
            return $bank_info;
        } catch(\PDOException $e) {
            throw new Exception("数据库查询错误",0,array(
                'sql'=>$sql,
                'error'=>$e->getMessage()
            ));
        }
    }
    public function saveMoney(string $token,string $uuid,int $save_money){
        $Token=new Token();
        $token_info=$Token->getTokenInfo($token);
        if($token_info['uuid']!=$uuid)
            throw new Exception('用户信息错误');
        if($save_money <= 0)
            throw new Exception('存入金额错误');
        $User=new User();
        $money=$User->getMoney($uuid);
        $wait_save_money = $this->where('uuid',$uuid)->find();
        $allmoney = $save_money + $wait_save_money['wait_save_money'];
        if($money < $allmoney)
            throw new Exception('账户余额不足');
        $this->where('uuid',$uuid)->update(array('wait_save_money'=>$allmoney));
        
    }
    public function takeMoney(string $token,string $uuid,int $take_money){
        $Token=new Token();
        $token_info=$Token->getTokenInfo($token);
        if($token_info['uuid']!=$uuid)
            throw new Exception('用户信息错误');
        if($take_money <= 0)
            throw new Exception('取出金额错误');
        $bankinfo = $this->where('uuid',$uuid)->find();
        $allmoney = $take_money + $bankinfo['wait_take_money'];
        if($bankinfo['money'] < $allmoney)
            throw new Exception('银行存款不足');
        $this->where('uuid',$uuid)->update(array('wait_take_money'=>$allmoney));
    }
}