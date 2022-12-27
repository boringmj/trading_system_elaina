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
            return '';
        }
        try {
            // 通过code获取信息
            $Award=new Award();
            $award_info=$Award->getAwardInfoByCode($code);
            if($award_info['check']!==0) {
                // 需要验证, 跳转到验证页面
                $data=array(
                    'uuid'=>$uuid,
                    'id'=>$award_info['id'],
                    'code'=>$code,
                    'key'=>App::getClass('Config')::get('app.config.all.user.key')
                );
                $server_sign=\AdminService\common\sign($data);
                $goto='/index/receive/check/id/'.$award_info['id'].'/sign'.'/'.$server_sign;
                $this->header('Location',$goto);
                return '';
            }
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
    
    public function check() {
        // 获取参数
        $uuid=$this->param('uuid','');
        $token=$this->param('token','');
        $id=$this->param('id',0);
        $sign=$this->param('sign','');
        if(empty($uuid)||empty($token)) {
            $goto='/index/view/login/goto/'.urlencode('/index/receive/check/id/'.$id.'/sign'.'/'.$sign);
            $this->header('Location',$goto);
            return '';
        }
        try {
            // 通过id获取code
            $Award=new Award();
            $code=$Award->getAwardInfoById((int)$id);
            $code=$code['code'];
            // 验证签名
            $data=array(
                'uuid'=>$uuid,
                'id'=>$id,
                'code'=>$code,
                'key'=>App::getClass('Config')::get('app.config.all.user.key')
            );
            $server_sign=\AdminService\common\sign($data);
            if($server_sign!=$sign)
                throw new Exception('签名错误: 请不要通过其他人的链接领取奖励');
            // 领取奖励
            $result=$Award->receiveAward($uuid,$token,$code,true);
            $currency_name=App::getClass('Config')::get('app.config.all.view.currency_name');
            return view('index',array(
                'title'=>'您发现了一个彩蛋',
                'msg'=>$result['remark']."<br>您因此获得了 <b>".$result['money']."</b> ".$currency_name
            ));
        } catch(Exception $e) {
            return view('index',array(
                'title'=>'领取失败',
                'msg'=>$e->getMessage()
            ));
        }
    }

}

?>