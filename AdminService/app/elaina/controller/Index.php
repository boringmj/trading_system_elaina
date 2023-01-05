<?php

namespace app\elaina\controller;

use app\elaina\model\Cdkey;
use app\elaina\model\Token;
use base\Controller;
use app\elaina\model\User;
use app\elaina\model\Time;
use app\elaina\model\Skins;
use AdminService\Exception;
use function AdminService\common\json;

class Index extends Controller {
        /**
     * 校验net_id
     * 
     * @access private
     * @param string $nid
     * @return bool
     */
    private function checkNetId(string $nid):bool{
        if(strlen($nid) != 20){
            return true;
        }
        if(substr($nid,1,2)!="U_" || !is_numeric(substr($nid,3,17))  ){
        return true;
        }
        return false;
    }
    /**
     * 校验klei_id
     * 
     * @access private
     * @param string $nid
     * @return bool
     */
    private function checkKleiId(string $kid):bool{
        if(strlen($kid) != 11){
            return true;
        }
        if(substr($kid,0,3) != "KU_"){
            return true;
        }
        return false;
    }
    /**
     * 校验cdk
     * 
     * @access private
     * @param string $cdk
     * @return bool
     */
    private function checkCdk(string $cdk):bool{
        $cdk = preg_replace('/[^A-Za-z0-9-]/','',$cdk);
        if (!$cdk) {
            return true;
        }
        if (strlen($cdk) != 23) {
            return true;
        }
        for ($x = 5; $x < 23; $x += 6) {
            if (strcmp(substr($cdk, $x, 1), "-") != 0) {
                return true;
            }
        }
        return false;
    } 
    public function index() {
        return 'Hello World!';
    }
    public function login()
    {
        $nid = $this->param('nid');
        $kid = $this->param('kid') == $nid ? '' : $this->param('kid');
        $kid = $kid ?? '';
        $nickname = $this->param('name')??'';
        //校验net_id
        if($this->checkNetId($nid))
            return json(-1, "nid error");
        // 校验klei_id
        if(!empty($kid)){
            if($this->checkKleiId($kid))
                return json(-1, "kid error");
        }
        try {
            $User = new User();
            $token = $User->login($kid,$nid,$nickname);
            return json(1,'ok',array('token'=>$token));
        } catch (Exception $e) {
            return json(-1,$e->getMessage());
        }
    }
    
    public function online(){
        $token = $this->param('token');
        $necklace_time = $this->param('necklace_time');
        
        if(!is_numeric($necklace_time)){
            return json(-1, "necklace time error");
        }
       
        try{
            $Time = new Time();
            $timeinfo = $Time->recordTime($token,$necklace_time);
            return json(1, 'ok', array(
                'necklace_time'=>$timeinfo['necklace_time'],
                'game_time'=>$timeinfo['game_time']
            ));
        }catch (Exception $e) {
            return json(-1,$e->getMessage());
        }
    } 
    public function getskins(){
        $kid = $this->param('kid');  
        if($this->checkKleiId($kid)){
            return json(-1,'klei_id error');
        }
        try{
            $Skins = new Skins();
            $skininfo = $Skins->getSkins($kid);
            return json(1, 'ok', $skininfo);
        }catch (Exception $e) {
            return json(-1,$e->getMessage());
        }
       
    }
    public function usecdk(){
        $token = $this->param('token');
        $cdk = $this->param('cdk');
        if($this->checkCdk($cdk)){
            return json(-1,'卡密不正确 请重新输入');
        }
        try{
            $Token = new Token();
            $userinfo = $Token->getTokenInfo($token);
            $Cdkey = new Cdkey();
            $Cdkey->cdkInit($cdk);
            $cdktype = $Cdkey->getCdkType();
            if($cdktype != 2){
                if (empty($userinfo['klei_id'])){
                    throw new Exception('您当前处于离线状态,无法激活该秘钥');
                }
            }
            switch ($cdktype) {
                case 0:
                    $Cdkey->tryUseCdkey();
                    $data = $Cdkey->useCdk($userinfo['klei_id']);
                    return json(1,'ok',$data);
                case 1:
                    $Cdkey->tryUseCdkey();
                    $data = $Cdkey->getRabbitYuan($userinfo['klei_id'], $userinfo['net_id']);
                    return json(1,'ok',$data);
                case 2:
                    $msg = $Cdkey->bindQQ($userinfo['klei_id'], $userinfo['net_id']);
                    return json(2,$msg);
                default:
                    return json(-1,'秘钥异常');
            }

        }catch (Exception $e){
            return json(-1,$e->getMessage());
        }
    }

    public function activeskin(){
        $skin = $this->param('skin');
        $token = $this->param('token');
        try {
            $Token = new Token();
            $userinfo = $Token->getTokenInfo($token);
            if (empty($userinfo['klei_id'])){
                throw new Exception('您当前处于离线状态,无法激活该秘钥');
            }
            $Skins = new Skins();
            if($skin === 'elaina_hl'){
                $data = $Skins->ActiveHl($userinfo['klei_id'], $userinfo['net_id']);
                return json(1, 'ok', $data);
            }else{
                $data = $Skins->ActiveHj($userinfo['klei_id'], $userinfo['net_id']);
                return json(1, 'ok', $data);
            }
        }catch (Exception $e){
            return json(-1,$e->getMessage());
        }
    }
}
