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
    public string $table_name='ssd_bank_award';

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
     * 通过 id 获取奖励信息
     * 
     * @access public
     * @param int $id 奖励 id
     * @return array
     * @throws Exception
     */
    public function getAwardInfoById(int $id): array {
        $award_info=$this->where('id',$id)->find();
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
     * @param bool $check 是否已经验证过
     * @return array
     * @throws Exception
     */
    public function receiveAward(string $uuid,string $token,string $code,bool $check=false): array {
        // 校验Token
        $Token=new Token();
        $user_info=$Token->getTokenInfo($token);
        if($user_info['uuid']!=$uuid)
            throw new Exception("令牌错误");
        // 获取奖励信息
        $award_info=$this->getAwardInfoByCode($code);
        // 判断是否需要验证
        if($award_info['check']!==0&&$check==false)
            throw new Exception("非法操作");
        // 判断是否已经领取
        $user_award_info=$this->table('ssd_bank_award_find')->where('uuid',$uuid)->where('pid',$award_info['id'])->find();
        $currency_name=App::getClass('Config')::get('app.config.all.view.currency_name');
        if(!empty($user_award_info))
            throw new Exception("您已经领取过了<br>于 ".date('Y-m-d H:i:s',$user_award_info['create_time'])."<br>领取 <b>{$user_award_info['money']}</b> {$currency_name}");
        // 判断是否已经过期
        if($award_info['expire_time']<time())
            throw new Exception("连接已经过期了");
        $money_min=round($award_info['min_money'],2)*100;
        $money_max=round($award_info['max_money'],2)*100;
        $money=round(mt_rand($money_min,$money_max)/100,2);
        if($award_info['money']==0)
            throw new Exception("连接已经过期了");
        if($award_info['money']<$money&&$award_info['money']>0)
            $money=$award_info['money'];
        try {
            // 获取银行的uuid
            $bank_uuid=App::getClass('Config')::get('app.config.all.system.uuid.bank');
            $Money=new Money();
            // 转账
            $Money->transferByUuid($uuid,$bank_uuid,$money,$award_info['remark']);
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
        // 记录领取记录
        $this->table('ssd_bank_award_find')->insert([
            'uuid'=>$uuid,
            'pid'=>$award_info['id'],
            'money'=>$money,
            'create_time'=>time()
        ]);
        // 扣除奖池余额
        if($award_info['money']>0)
            $this->where('id',$award_info['id'])->update([
                'money'=>$award_info['money']-$money
            ]);
        return [
            'money'=>$money,
            'remark'=>$award_info['remark']
        ];
    }

}