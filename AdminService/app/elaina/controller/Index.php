<?php

namespace app\index\controller;

use base\Controller;
use app\elaina\model\Time;
use  app\elaina\model\User;
use AdminService\Exception;
use function AdminService\common\json;

class Index extends Controller {
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
