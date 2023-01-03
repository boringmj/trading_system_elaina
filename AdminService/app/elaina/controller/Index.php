<?php

namespace app\elaina\controller;

use base\Controller;
use app\elaina\model\Time;
use  app\elaina\model\User;
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
    
    public function index() {
        return 'Hello World!';
    }
    public function login()
    {
        $nid = $this->param('nid');
        $kid = $this->param('kid') == $nid ? '' : $this->param('kid');
        $nickname = $this->param('name');
        if ($nid == null) {
            return json(-1, "nid or kid error");
        }
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
}
