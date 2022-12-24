<?php

namespace app\index\model;

use base\Model;
use AdminService\App;
use AdminService\Exception;
use AdminService\model\Token;
use AdminService\model\Money;

class Award extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='trading_system_elaina_bank_award';

    /**
     * 通过 code 获取奖励信息
     * 
     * @access public
     * @param string $code 奖励 code
     * @return array
     * @throws Exception
     */
    public function getAwardInfoByCode(string $code): array {
        $award_info=$this->where('code',$code)->find();
        if(empty($award_info))
            throw new Exception("链接不存在");
        return $award_info;
    }

    /**
     * 通过 uuid, token 和 code 来领取奖励
     * 
     * @access public
     * @param string $uuid 用户 uuid
     * @param string $token 用户 token
     * @param string $code 奖励 code
     * @return array
     * @throws Exception
     */
    public function receiveAward(string $uuid,string $token,string $code): array {
        // 校验Token
        $Token=new Token();
        $user_info=$Token->getTokenInfo($token);
        if($user_info['uuid']!=$uuid)
            throw new Exception("Token错误");
        // 获取奖励信息
        $award_info=$this->getAwardInfoByCode($code);
        // 判断是否已经领取
        $user_award_info=$this->table('trading_system_elaina_bank_award_find')->where('uuid',$uuid)->where('pid',$award_info['id'])->find();
        if(!empty($user_award_info))
            throw new Exception("已经领取过了");
        // 判断是否已经过期
        if($award_info['expire_time']<time())
            throw new Exception("连接已经过期了");
        $money_min=round($award_info['min_money'],2)*100;
        $money_max=round($award_info['max_money'],2)*100;
        $money=round(mt_rand($money_min,$money_max)/100,2);
        try {
            // 获取银行的uuid
            $bank_uuid=App::getClass('Config')::get('app.config.all.system.uuid.bank');
            $Money=new Money();
            // 转账
            $Money->transferByUuid($uuid,$bank_uuid,$money,$award_info['remark']);
        } catch(Exception $e) {
            throw new Exception("领取失败");
        }
        // 记录领取记录
        $this->table('trading_system_elaina_bank_award_find')->insert([
            'uuid'=>$uuid,
            'pid'=>$award_info['id'],
            'money'=>$money,
            'create_time'=>time()
        ]);
        return [
            'money'=>$money,
            'remark'=>$award_info['remark']
        ];
    }

}