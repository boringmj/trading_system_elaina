<?php

namespace app\elaina\controller;

use AdminService\Log;
use base\Controller;
use app\elaina\model\Cdkey;
use app\elaina\model\Token;
use app\elaina\model\User;
use app\elaina\model\Time;
use app\elaina\model\Skins;
use AdminService\Exception;
use function AdminService\common\json;

class Index extends Controller
{
    /**
     * 校验net_id
     * 
     * @access private
     * @param string $nid
     * @return bool
     */
    private function checkNetId(string $nid): bool
    {
        if(empty($nid)){
            return true;
        }
        if (strlen($nid) != 20) {
            return true;
        }
        if (substr($nid, 1, 2) != "U_" || !is_numeric(substr($nid, 3, 17))) {
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
    private function checkKleiId(string $kid): bool
    {
        if(empty($kid)){
            return true;
        }
        if (strlen($kid) != 11) {
            return true;
        }
        if (substr($kid, 0, 3) != "KU_") {
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
    private function checkCdk(string $cdk): bool
    {
        $cdk = preg_replace('/[^A-Za-z0-9-]/', '', $cdk);
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
    public function index()
    {
        return 'Hello World!';
    }
    public function login()
    {
        $nid = $this->param('nid')??'';
        $kid = $this->param('kid') == $nid ? '' : $this->param('kid');
        $kid = $kid ?? '';
        $nickname = $this->param('name') ?? '';
        //preg_match_all('/([a-zA-Z0-9_\x{4e00}-\x{9fa5}])+/u', $nickname, $data);
        $nickname = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $nickname);
        // 将结果的第一个字符集合并
        //$nickname = implode($data[0]??[])??'';
        //校验net_id
        if ($this->checkNetId($nid))
            return json(-1, "nid error");
        // 校验klei_id
        if (!empty($kid)) {
            if ($this->checkKleiId($kid))
                return json(-1, "kid error");
        }
        try {
            $User = new User();
            $token = $User->login($kid, $nid, $nickname);
            $Time = new Time();
            $timeinfo = $Time->getTime($nid);
            $data = array('token' => $token,'game_time'=>$timeinfo['game_time'],'necklace_time'=>$timeinfo['necklace_time']);
            return json(1, 'ok', $data);
        } catch (Exception $e) {
            return json(-1, $e->getMessage());
        }
    }

    public function online()
    {
        $token = $this->param('token')??'';
        $necklace_time = $this->param('necklace_time');

        if (!is_numeric($necklace_time)) {
            return json(-1, "necklace time error");
        }
        try {
            $Time = new Time();
            $timeinfo = $Time->recordTime($token, $necklace_time);
            return json(1, 'ok', array(
                'necklace_time' => $timeinfo['necklace_time'],
                'game_time' => $timeinfo['game_time']
            ));
        } catch (Exception $e) {
            return json(-1, $e->getMessage());
        }
    }
    public function getskins()
    {
        $kid = $this->param('kid')??'';
        if ($this->checkKleiId($kid)) {
            return json(-1, 'klei_id error');
        }
        try {
            $Skins = new Skins();
            $skininfo = $Skins->getSkinsFromDST($kid);
            return json(1, 'ok', $skininfo);
        } catch (Exception $e) {
            return json(-1, $e->getMessage());
        }
    }
    public function usecdk()
    {
        $token = $this->param('token')??'';
        $cdk = $this->param('cdk')??'';
        // $log=new Log("usingcdk");
        // $log->write($cdk);
        if ($this->checkCdk($cdk)) {
            return json(-1, '卡密不正确,请重新输入');
        }
        try {
            $Token = new Token();
            $userinfo = $Token->getTokenInfo($token);
            $Cdkey = new Cdkey();
            $Cdkey->cdkInit($cdk);
            $cdktype = $Cdkey->getCdkType();
            if ($cdktype != 2) {
                if (empty($userinfo['klei_id'])) {
                    throw new Exception('您当前处于离线状态,无法激活该秘钥');
                }
            }
            switch ($cdktype) {
                case 0:
                    $Cdkey->tryUseCdkey();
                    $data = $Cdkey->useCdk($userinfo['klei_id']);
                    return json(1, 'ok', $data);
                case 1:
                    $Cdkey->tryUseCdkey();
                    $data = $Cdkey->getRabbitYuan($userinfo['klei_id'], $userinfo['net_id']);
                    return json(1, 'ok', $data);
                case 2:
                    $msg = $Cdkey->bindQQ($userinfo['klei_id'], $userinfo['net_id']);
                    return json(2, $msg);
                default:
                    return json(-1, '秘钥异常');
            }
        } catch (Exception $e) {
            return json(-1, $e->getMessage());
        }
    }

    public function activeskin()
    {
        $skin = $this->param('skin')??'';
        $token = $this->param('token')??'';
        try {
            $Token = new Token();
            $userinfo = $Token->getTokenInfo($token);
            if (empty($userinfo['klei_id'])) {
                throw new Exception('您当前处于离线状态,无法激活该秘钥');
            }
            $Skins = new Skins();
            if ($skin === 'elaina_hl') {
                $data = $Skins->ActiveHl($userinfo['klei_id'], $userinfo['net_id']);
                return json(1, 'ok', $data);
            } else {
                $data = $Skins->ActiveHj($userinfo['klei_id'], $userinfo['net_id']);
                return json(1, 'ok', $data);
            }
        } catch (Exception $e) {
            return json(-1, $e->getMessage());
        }
    }
    public function registergift()
    {
        $token = $this->param('token')??'';
        try {
            $Token = new Token();
            $userinfo = $Token->getTokenInfo($token);
            if (empty($userinfo['klei_id'])) {
                throw new Exception('当前处于离线状态,无法获取皮肤');
            }
            $skingift = array();
            $Skins = new Skins();
            $skinlist = $Skins->getLongSkins($userinfo['klei_id']);
            $User = new \AdminService\model\User();
            $item_id = 0 ;
            if($User->getUserIsRegister($userinfo['net_id'])){
                if(!in_array("magic_wand_cj_skin_lan",$skinlist)){
                    $Skins->activationSkins($userinfo['klei_id'], 'magic_wand_cj_skin_lan', '岚');
                    $skingift[] = array('item' => 'magic_wand_cj_skin_lan', 'item_id' => $item_id,'gifttype'=>'ELAINASKIN');
                }
                if(!in_array("magic_wand_cj_skin_dai",$skinlist)){
                    $Skins->activationSkins($userinfo['klei_id'], 'magic_wand_cj_skin_dai', '黛');
                    $skingift[] = array('item' => 'magic_wand_cj_skin_dai', 'item_id' => $item_id,'gifttype'=>'ELAINASKIN');
                }
            }
            if(in_array('elaina_alxy',$skinlist) && !in_array('elaina_bag_skin_altu',$skinlist)){
                $Skins->activationSkins($userinfo['klei_id'], 'elaina_bag_skin_altu', '爱莉兔');
                $skingift[] = array('item' => 'elaina_bag_skin_altu', 'item_id' => $item_id,'gifttype'=>'ELAINASKIN');
            }
            if(empty($skingift)){
                return json(-1, 'nogift');
            }else{
                return json(1, 'ok',$skingift);
            }
        } catch (Exception $e) {
            return json(-1, $e->getMessage());
        }
    }
    public function getplayerlist()
    {
        $data = array(  
        );
        return json(1, 'ok', $data);
    }
}
