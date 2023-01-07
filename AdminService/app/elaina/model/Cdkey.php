<?php

namespace app\elaina\model;
use base\Model;
use AdminService\App;
use app\elaina\model\Skins;
use AdminService\Exception;
use AdminService\model\User;
use AdminService\model\Money;
class Cdkey extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='ssd_elaina_cdkey';
    public  string $cdk = '';
    public  int $cdk_type = 0;
    private int   $value = 0;
    private int   $type = 0;
    private bool  $lock = false;
    private int $create_time = 0;
    private int $cdk_expire_time = 30;
    private array $skininfo = array();
    private string $qq = '';
    private string $net_id = '';
    /**
     * 初始化cdk信息
     * 
     * @access public
     * @param string $kid 用户克雷ID
     * @return bool
     * @throws Exception
     */
    public function cdkInit(string $cdk):bool{
        $this->cdk = $cdk;
        $skincdkinfo = $this->where('cdk', $cdk)->find();
        if(!empty($skincdkinfo)){
            $this->value = $skincdkinfo['value'];
            $this->type = $skincdkinfo['type'];
            $this->lock = $skincdkinfo['lock'] === 1;
            $this->create_time = strtotime($skincdkinfo['create_time']);
            $this->cdk_expire_time =$skincdkinfo['cdk_expire_time'];

            $skininfo = $this->table('ssd_elaina_cdkey_info')->where('cdk', $cdk)->where('user_kid','')->select();
            if(empty($skininfo)){
                throw new Exception('这张卡密的全部皮肤已被使用');
            }
            $this->skininfo = $skininfo;
            if($this->value < 0){
                $this->cdk_type = 0;
            }else{
                $this->cdk_type = 1;
            }
            return true;
        }
        $bindcodeinfo = $this->table('ssd_elaina_usercode')->where('bindcode', $cdk)->find();
        if(empty($bindcodeinfo)){
            throw new Exception('卡密不正确,请重新输入');
        }
        if(!empty($bindcodeinfo['net_id'])){
            throw new Exception('该绑定码已被使用');
        }
        $this->qq = $bindcodeinfo['qq'];
        $this->net_id = $bindcodeinfo['net_id'];
        $this->cdk_type = 2;
        return true;
    }
    //cdk类型,0皮肤,1礼品卡,2绑定码
    public function getCdkType():int{
        return $this->cdk_type ;
    }

    /**
     * 判断cdk是否可以使用
     * @access public
     * @return void
     * @throws Exception
     */
    public function tryUseCdkey():void{
        if (empty($this->skininfo)){
            throw new Exception('该cdk已被使用');
        }
        if($this->lock){
            throw new Exception('该cdk已被锁定');
        }
    }
    /**
     * 更新cdk信息
     * @access public
     * @param string $kid 用户克雷ID
     * @param int $id 皮肤id
     * @return void
     * @throws Exception
     */    
    public function updateCdk(int $id, string $kid): void {
        $time = date('Y-m-d H:i:s',time());
        $this->table('ssd_elaina_cdkey_info')->where('id', $id)->update(
            array(
                'user_kid'=>$kid,
                'use_time'=>$time
            )
        );
    }

    /**
     * 尝试激活cdk
     * @access public
     * @param string $kid 用户克雷ID
     * @return array
     * @throws Exception
     */
    public function useCdk(string $kid): array {
        $Skins = new Skins();
        $item_id = 0;
        $skingift = array();
        foreach ($this->skininfo as $value) {
            if ($Skins->hasSkins($kid,$value['skinprefab'])){
                break;
            }
            $this->updateCdk($value['id'],$kid);
            if($value['skin_expire_time'] < 0){
                $Skins->activationSkins($kid,$value['skinprefab'],$value['skinname']);
            }else{
                $Skins->activationTempSkins($kid,$value['skinprefab'],$value['skinname'],$value['skin_expire_time']);
            }
            $skingift[] = array('item' => $value['skinprefab'], 'item_id' => $item_id,'gifttype'=>'ELAINASKIN');
            $item_id++;
        }
        if(empty($skingift)){
            throw new Exception('你已经拥有该秘钥的所有皮肤');
        }
        return $skingift;
    }
    /**
     * 使用礼品卡
     * @access public
     * @param string $net_id 用户Net_ID
     * @param string $klei_id 用户克雷ID
     * @return array
     * @throws Exception
     */
    public function getRabbitYuan(string $kid, string $nid): array {
        $cardinfo = $this->table('ssd_elaina_cdkey_info')->where('cdk', $this->cdk)->select();
        foreach ($cardinfo as $value) {
            if ($value === $kid)
                throw new Exception('这张卡密已经被你使用过了');
        }
        //获取用户uuid信息
        $User = new User();
        $userinfo = $User->getUserInfoByUserName($nid);
        $Money = new Money();
        //兔元从系统用户扣除
        $system_uuid = App::getClass('Config')::get('app.config.all.system.uuid.system_user');
        //更新cdk信息
        $time = date('Y-m-d H:i:s',time());
        $this->table('ssd_elaina_cdkey_info')->where('id', $this->skininfo[0]['id'])->update(
            array(
                'user_kid'=>$kid,
                'use_time'=>$time
            )
        );
        $Money->transferByUuid($userinfo['uuid'], $system_uuid, $this->value);
        //返回礼物信息
        return array('item' => 'rabbityuan', 'item_id' => 0,'gifttype'=>'ELAINASKIN');
    }
    /**
     * 用户是否已经绑定
     * @access public
     * @param string $nid 用户Net_ID
     * @return bool
     */
    public function userIsBind(string $nid):bool{
        $bindinfo = $this->table('ssd_elaina_usercode')->where('net_id', $nid)->find();
        if(empty($bindinfo)){
            return false;
        }
        return true;
    }
    /**
     * 绑定
     * @access public
     * @param string $net_id 用户Net_ID
     * @param string $klei_id 用户克雷ID
     * @return string
     * @throws Exception
     */
    public function bindQQ(string $kid,string $nid) : string{
        if($this->userIsBind($nid)){
            throw new Exception('你已经绑定过QQ,请勿重复绑定');
        }
        $time = date('Y-m-d H:i:s',time());
        $this->table('ssd_elaina_usercode')->where('usercode',$this->cdk)->update(array(
            'klei_id'=>$kid,
            'net_id'=>$nid,
            'bindcode'=>null,
            'bind_time'=>$time
        ));
        return '绑定成功,QQ:'.$this->qq;
    }
}