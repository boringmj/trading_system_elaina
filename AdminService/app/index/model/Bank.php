<?php

namespace app\index\model;

use base\Model;
use AdminService\Config;
use AdminService\Exception;
use AdminService\model\User;

class Bank extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='trading_system_elaina_bank';

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
            $bank_info=$stm->fetchAll();
            return $bank_info;
        } catch(\PDOException $e) {
            throw new Exception("数据库查询错误",0,array(
                'sql'=>$sql,
                'error'=>$e->getMessage()
            ));
        }
    }

}