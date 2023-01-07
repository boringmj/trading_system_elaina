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
            return json(1, 'ok', array('token' => $token));
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
            $skininfo = $Skins->getSkins($kid);
            return json(1, 'ok', $skininfo);
        } catch (Exception $e) {
            return json(-1, $e->getMessage());
        }
    }
    public function usecdk()
    {
        $token = $this->param('token')??'';
        $cdk = $this->param('cdk')??'';
        $log=new Log("debug");
        $log->write($cdk);
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






    public function getplayerlist()
    {
        $data = array(
            array(
                'name' => '魔女之旅制作组',
                'playerlist' => array(
                    'KU_OdKe1Nd-', 'KU_fNVmfZVX', 'KU_1_oDYw0W', 'KU_KSZYEf7O', 'KU_cuIT12cs'
                )
            ),
            array(
                'name' => '二七',
                'playerlist' => array(
                'KU_xb1Ljly4', 'KU_pvwb-aQC', 'KU_LT2_XE2R'
                )
                ),
                array(
                'name' => '要不我们跑路吧',
                'playerlist' => array(
                'KU_FSLwP4Wi',
                'KU_cuIT1qBC',
                'KU_GuSGiWW6'
                )
                ),
                array(
                'name' => '一杆到底队',
                'playerlist' => array(
                'KU_1W-yHFdB', 'KU _5QNaVCZg', 'KKU_FSLwP508'
                )
                ),
                array(
                'name' => '夜间垂钓',
                'playerlist' => array(
                'KU_0cJfzwp2',
                'KU_FM5EhCDG',
                'KU_u0cSuP7L'
                )
                ),
                array(
                'name' => '虽说歌声没有形状',
                'playerlist' => array(
                'KU_tvX4yQEA', 'KU_2dotE_Yi', 'KU_9oJuMaUs'
                )
                ),
                array(
                'name' => '余生请多指教',
                'playerlist' => array(
                'KU_N6-DLpU1',
                'KU_MAFS6riE',
                'KU_GNdCpabg'
                )
                ),
                array(
                'name' => '悠远の天空',
                'playerlist' => array(
                'KU_ZeQiu-UN', 'KU_osFM6lLc', 'KU_54sjctAa'
                )
                ),
                array(
                'name' => '喵球恰鱼队',
                'playerlist' => array(
                'KU_3NiPPsYF',
                'KU_54sjc5UT',
                'KU_sv5Nunrn'
                )
                ),
                array(
                'name' => '渔泽陌墨',
                'playerlist' => array(
                'KU_54sjc0--', 'KU_LI3oFFAp', 'KU_F4GEm0Ca'
                )
                ),
                array(
                'name' => '雾雨爱丽丝',
                'playerlist' => array(
                'KU_F4GEmed2',
                'KU_O9dsjEAY',
                'KU_nN4WNaNx'
                )
                ),
                array(
                'name' => '摸摸鱼',
                'playerlist' => array(
                'KU_nd-A3C7b', 'KU_BQAUzvP-', 'KU_84Tfujf1'
                )
                ),
                array(
                'name' => '摸鱼',
                'playerlist' => array(
                'KU_uerQE8jn',
                'KU_wZ_k_Jb4',
                'KU_bzmlyajx'
                )
                ),
                array(
                'name' => '坤妮肽梅队',
                'playerlist' => array(
                'KU_HQp7An5A', 'KU_OdKe1JU6', 'KU_iNDU0CF3'
                )
                ),
                array(
                'name' => '透透奶茶队',
                'playerlist' => array(
                'KU_c1gvb3dr',
                'KU_8EfLHgXx' )
                ),
                array(
                'name' => '遗憾的不该是我',
                'playerlist' => array(
                'KU_bkBUBcrl', 'KU_iJIpbdwK', 'KU_vCuBS_rj'
                )
                ),
                array(
                'name' => '拉拉队',
                'playerlist' => array(
                'KU_pa5CjQUO',
                'KU_voGjENCi',
                'KU_LI3oE8tC'
                )
                ),
                array(
                'name' => '九轩',
                'playerlist' => array(
                'KU_3R54yHR0', 'KU_ZG3VPAK1', 'KU_3xJ72Emv'
                )
                ),
                array(
                'name' => '小狐狸',
                'playerlist' => array(
                'KU_HDjLJFqk' )
                ),
                array(
                'name' => '里苏特',
                'playerlist' => array(
                'KU_tO6yAnWn' )
                ),
                array(
                'name' => '琉璃',
                'playerlist' => array(
                'KU_uerQE_9Z' )
                ),
                array(
                'name' => 'Jian',
                'playerlist' => array(
                'KU_YSIGJ1ab' )
                ),
                array(
                'name' => '道友清香白莲',
                'playerlist' => array(
                'KU_c1gvcMKq' )
                ),
                array(
                'name' => '见习猎人-小鹰',
                'playerlist' => array(
                'KU_VA9TCd_c' )
                ),
                array(
                'name' => '小凤凰',
                'playerlist' => array(
                'KU_V_2d00g3' )
                ),
                array(
                'name' => '浮世千寻',
                'playerlist' => array(
                'KU_iJIpcTX2', 'KU_IDYsmzgF', 'KU_VA9TCbwF'
                )
                ),
                array(
                'name' => '娜娜奇',
                'playerlist' => array(
                'KU_A0x_UJvu' )
                ),
                array(
                'name' => '脚不沾地',
                'playerlist' => array(
                'KU_MitzUq_K', 'KU_L3IC3-JG', 'KU__CRUc2mD'
                )
                ),
                array(
                'name' => '保证金',
                'playerlist' => array(
                'KU_oX0uXoyY' )
                ),
                array(
                'name' => '瑞琪不是黑琪鹰',
                'playerlist' => array(
                'KU_RxpQoi1r' )
                ),
                array(
                'name' => '怪盗キッド 黑羽快斗',
                'playerlist' => array(
                'KU_LI3oFE_2' )
                ),
                array(
                'name' => '噗噗爱吃肉肉',
                'playerlist' => array(
                'KU_MAFS6mWW', 'KU_vCuBSj7g' )
                ),
                array(
                'name' => '我要吃鱼',
                'playerlist' => array(
                'KU_7muUzs1g' )
                ),     
        );
        return json(1, 'ok', $data);
    }
}
