<?php

namespace AdminService\model;

use base\Model;
use AdminService\App;
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

    /**
     * 通过qq为用户转入一笔交易记录
     * 
     * @access public
     * @param string $qq 用户qq
     * @param string $from_qq 来源用户qq
     * @param float $money 交易金额
     * @param string $remark 交易备注
     * @return void
     * @throws Exception
     */
    public function transferByQq(string $qq,string $from_qq,float $money,string $remark='') {
        // 获取用户的qq号
        $User=new User();
        $uuid=$User->where('qq',$qq)->find('uuid');
        $from_uuid=$User->where('qq',$from_qq)->find('uuid');
        // 验证用户是否存在
        if(empty($uuid)||empty($from_uuid))
            throw new Exception("用户不存在",0,array(
                'qq'=>$qq,
                'from_qq'=>$from_qq
            ));
        // 转入交易记录
        $this->transferByUuid($uuid,$from_uuid,$money,$remark);
    }

    /**
     * 通过来源回滚所有用户余额到某个时间点
     * 
     * @access public
     * @param string $from_uuid 来源用户ID
     * @param int $time 时间戳
     * @return void
     * @throws Exception
     */
    public function rollbackByFromUuid(string $from_uuid,int $time) {
        // 获取用户信息
        $User=new User();
        $user_money_from=$User->getMoney($from_uuid);
        // 获取所有交易记录
        $money_list=$this->where('from_uuid',$from_uuid)->where('create_time',$time,'>=')->select();
        // 回滚用户余额(事务处理)
        $User->beginTransaction();
        $this->beginTransaction();
        App::get('Log')->write('发生回滚事件-{name} | 来源: {from}, 时间: {time}, 至: {to_time}',array(
            'name'=>'转账回滚-来源',
            'from'=>$from_uuid,
            'time'=>date('Y-m-d H:i:s'),
            'to_time'=>date('Y-m-d H:i:s',$time)
        ));
        try {
            foreach($money_list as $money) {
                $user_money_to=$User->getMoney($money['uuid']);
                $User->where('uuid',$money['uuid'])->update(array(
                    'money'=>$user_money_to-$money['money']
                ));
                $User->where('uuid',$from_uuid)->update(array(
                    'money'=>$user_money_from+$money['money']
                ));
                $user_money_from+=$money['money'];
                // 新增一条回滚记录
                $this->insert(array(
                    'uuid'=>$money['uuid'],
                    'from_uuid'=>$from_uuid,
                    'money'=>-$money['money'],
                    'remark'=>'余额回滚-01',
                    'create_time'=>time()
                ));
                App::get('Log')->write('回滚成功-{name} | 来源: {from}, 目标: {to}, 金额: {money}',array(
                    'name'=>'转账回滚',
                    'from'=>$from_uuid,
                    'to'=>$money['uuid'],
                    'money'=>$money['money']
                ));
            }
            $User->commit();
            $this->commit();
        } catch(Exception $e) {
            App::get('Log')->write('回滚失败-{error}',array(
                'error'=>$e->getMessage()
            ));
            $User->rollback();
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 通过备注回滚所有用户余额到某个时间点
     * 
     * @access public
     * @param string $remark 备注
     * @param int $time 时间戳
     * @return void
     * @throws Exception
     */
    public function rollbackByRemark(string $remark,int $time) {
        // 获取所有交易记录
        $money_list=$this->where('remark',$remark)->where('create_time',$time,'>=')->select();
        // 回滚用户余额(事务处理)
        $User=new User();
        $User->beginTransaction();
        $this->beginTransaction();
        App::get('Log')->write('发生回滚事件-{name} | 备注: {remark}, 时间: {time}, 至: {to_time}',array(
            'name'=>'转账回滚-备注',
            'remark'=>$remark,
            'time'=>date('Y-m-d H:i:s'),
            'to_time'=>date('Y-m-d H:i:s',$time)
        ));
        try {
            foreach($money_list as $money) {
                $user_money_to=$User->getMoney($money['uuid']);
                $user_money_from=$User->getMoney($money['from_uuid']);
                $User->where('uuid',$money['uuid'])->update(array(
                    'money'=>$user_money_to-$money['money']
                ));
                $User->where('uuid',$money['from_uuid'])->update(array(
                    'money'=>$user_money_from+$money['money']
                ));
                // 新增一条回滚记录
                $this->insert(array(
                    'uuid'=>$money['uuid'],
                    'from_uuid'=>$money['from_uuid'],
                    'money'=>-$money['money'],
                    'remark'=>'余额回滚-02',
                    'create_time'=>time()
                ));
                App::get('Log')->write('回滚成功-{name} | 来源: {from}, 目标: {to}, 金额: {money}',array(
                    'name'=>'转账回滚',
                    'from'=>$money['from_uuid'],
                    'to'=>$money['uuid'],
                    'money'=>$money['money']
                ));
            }
            $User->commit();
            $this->commit();
        } catch(Exception $e) {
            App::get('Log')->write('回滚失败-{error}',array(
                'error'=>$e->getMessage()
            ));
            $User->rollback();
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 通过UUID查询用户所有交易记录
     * 
     * @access public
     * @param string $uuid 用户ID
     * @return array
     */
    public function getMoneyListByUuid(string $uuid) {
        $money_to_list=$this->where('uuid',$uuid)->select();
        $money_from_list=$this->where('from_uuid',$uuid)->select();
        return array(
            'to'=>$money_to_list,
            'from'=>$money_from_list
        );
    }
}