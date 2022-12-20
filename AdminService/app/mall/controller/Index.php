<?php

namespace app\mall\controller;

use base\Controller;
use app\mall\model\Mall;
use AdminService\Config;
use AdminService\Exception;

use function AdminService\common\json;
use function AdminService\common\sign;

class Index extends Controller {

    public function put() {
        $cdk=$this->param('cdk','');
        $qq=$this->param('qq','');
        $price=$this->param('price','');
        $sign=$this->param('sign','');
        // 验证参数
        if(empty($cdk))
            return json(1,'cdk不能为空');
        if(empty($qq))
            return json(1,'qq不能为空');
        if(empty($price))
            return json(1,'价格不能为空');
        // 验证签名
        $data=array(
            'cdk'=>$cdk,
            'qq'=>$qq,
            'price'=>$price,
            'token'=>Config::get('app.config.all.user.key')
        );
        $server_sign=sign($data);
        if($server_sign!=$sign)
            return json(-1,'签名错误');
        try {
            $Mall=new Mall();
            $result=$Mall->putOnByQq($cdk,$qq,$price);
            return json(1,'上架成功',$result);
        } catch (Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

    public function list() {
        $page=(int)$this->param('page',1);
        $limit=(int)$this->param('limit',20);
        // 验证参数
        if($page<1)
            $page=1;
        if($limit<1)
            $limit=20;
        try {
            $Mall=new Mall();
            $result=$Mall->getList($page,$limit);
            return json(1,'获取成功',$result);
        } catch (Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

}

?>
