<?php

namespace app\index\controller;

use base\Controller;
use AdminService\App;
use AdminService\Exception;
use app\index\model\Award;

use function AdminService\common\view;

class Receive extends Controller {

    public function index() {
        // 获取参数
        $uuid=$this->param('uuid','');
        $token=$this->param('token','');
        $code=$this->param('code','');
        if(empty($uuid)||empty($token)) {
            $goto='/index/view/login/goto/'.urlencode('/index/receive/index/code/'.$code);
            $this->header('Location',$goto);
        }
        try {
            // 领取奖励
            $Award=new Award();
            $result=$Award->receiveAward($uuid,$token,$code);
            $currency_name=App::getClass('Config')::get('app.config.all.view.currency_name');
            return view(array(
                'title'=>'您发现了一个彩蛋',
                'msg'=>$result['remark']."<br>您因此获得了 <b>".$result['money']."</b> ".$currency_name
            ));
        } catch(Exception $e) {
            return view(array(
                'title'=>'领取失败',
                'msg'=>$e->getMessage()
            ));
        }
    }

}

?>