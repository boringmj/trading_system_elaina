<?php

namespace app\elaina\model;
use base\Model;
use AdminService\Exception;

class Skins extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='ssd_elaina_skins';

    /**
     * 获取用户皮肤信息
     * 
     * @access public
     * @param string $kid 用户克雷ID
     * @return array
     * @throws Exception
     */
    public function getSkins(string $kid): array {
        $playerskins = array('items'=>array(),'temps'=>array());
        $skinsinfo = $this->where('klei_id',$kid)->select('skinprefab');
        if(!empty($skinsinfo)){
            foreach ($skinsinfo as $key => $value) {
                $playerskins['items'][] = $value['skinprefab'] ;
            }
        }
        $skinsinfo_temp = $this->table('ssd_elaina_skins_temp')->where('klei_id',$kid)->select('skinprefab');
        if(!empty($skinsinfo_temp)){
            foreach ($skinsinfo_temp as $key => $value) {
                $playerskins['temps'][] = $value['skinprefab'] ;
            }
        }
        // 返回皮肤信息
        return $playerskins;
    }

    /**
     * 用户是否拥有该皮肤
     * 
     * @access public
     * @param string $kid 用户克雷ID
     * @param string $skinprefab 皮肤代码
     * @return bool
     * @throws Exception
     */
    public function hasSkins(string $kid,string $skinprefab): bool {
        $skin = $this->where('klei_id', $kid)->where('skinprefab', $skinprefab)->find();
        if (!empty($skin))
            return true;
        $skin = $this->table('ssd_elaina_skins_temp')->where('klei_id', $kid)->where('skinprefab', $skinprefab)->find();
            if (!empty($skin))
                return true;
        return false;
    }

    /**
     * 激活永久皮肤
     * 
     * @access public
     * @param string $kid 用户克雷ID
     * @param string $skinprefab 皮肤代码
     * @param string $skinname 皮肤名
     * @param string $type 皮肤类型
     * @return void
     * @throws Exception
     */
    public function activationSkins(string $kid,string $skinprefab,string $skinname = '未知',int $type = 0): void {
        $this->insert(array(
            'skinprefab'=>$skinprefab,
            'skinname'=>$skinname,
            'klei_id'=>$kid,
            'type'=>$type
        ));
    }
    /**
     * 激活限时皮肤
     * 
     * @access public
     * @param string $kid 用户克雷ID
     * @param string $skinprefab 皮肤代码
     * @param string $skinname 皮肤名
     * @param string $type 皮肤类型
     * @param int $effect_time 有效期
     * @return void
     * @throws Exception
     */
    public function activationTempSkins(string $kid,string $skinprefab,string $skinname = '未知',int $effect_time = 604800,int $type = 0): void {
        $skininfo = $this->table('ssd_elaina_skins_temp')->where('skinprefab', $skinprefab)->where('keli_id', $kid)->find();
        $expire_time = time() + $effect_time * 24 * 3600 ;
        if(!empty($skininfo)){
            $expire_time = strtotime($skininfo['expire_time'])  + $effect_time;
            $this->table('ssd_elaina_skins_temp')->where('skinprefab', $skinprefab)->where('keli_id', $kid)->delete();
        }
        $this->table('ssd_elaina_skins_temp')->insert(array(
            'skinprefab'=>$skinprefab,
            'skinname'=>$skinname,
            'klei_id'=>$kid,
            'type'=>$type,
            'expire_time' => date("Y-m-d H:i:s",$expire_time)
        ));
    }

    /**
     * 激活海拉
     * 
     * @access public
     * @param string $kid 用户克雷ID
     * @param string $nid 用户Net_Id
     * @return array
     * @throws Exception
     */
    public function ActiveHl(string $kid, string $nid):array{
        if($this->hasSkins($kid,'elaina_hl')){
            throw new Exception('你已拥有[海拉cos],无需重复激活');
        }
        $Time = new Time();
        $timeinfo = $Time->getTime($nid);
        if($timeinfo['necklace_time'] < 6000){
            throw new Exception('时间计算出错,请半小时后再试');
        }
        $this->activationSkins($kid,'elaina_hl','海拉cos');
        return array('item' => 'elaina_hl', 'item_id' => 0,'gifttype'=>'ELAINASKIN');
    }

    /**
     * 激活花嫁
     * 
     * @access public
     * @param string $kid 用户克雷ID
     * @param string $nid 用户Net_Id
     * @return array
     * @throws Exception
     */
    public function ActiveHj(string $kid, string $nid):array{
        if($this->hasSkins($kid,'elaina_hl')){
            throw new Exception('你已拥有[花嫁],无需重复激活');
        }
        $Time = new Time();
        $timeinfo = $Time->getTime($nid);
        if($timeinfo['necklace_time'] < 3000){
            throw new Exception('时间计算出错,请半小时后再试');
        }
        //$this->activationSkins($kid,'elaina_hl','海拉cos');
        return array('item' => 'elaina_hj', 'item_id' => 0,'gifttype'=>'ELAINASKIN');
    }

    /**
     * 删除皮肤
     * 
     * @access public
     * @param string $kid 用户克雷ID
     * @param string $skinprefab 皮肤代码
     * @return void
     * @throws Exception
     */
    public function deleteSkins(string $kid,string $skinprefab): void {
        $this->where('skinprefab', $skinprefab)->where('klei_id',$kid)->delete();
    }
    /**
     * 清理过期皮肤
     * 
     * @access public
     * @return void
     * @throws Exception
     */
    public function cleanExpireSkin(): void {
        $time = date("Y-m-d H:i:s",time());
        $this->table('ssd_elaina_skins_temp')->where('expire_time',$time,'<')->delete();
    }
}