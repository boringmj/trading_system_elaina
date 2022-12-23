<?php

namespace app\mall\controller;

use base\Controller;
use app\mall\model\Mall;
use AdminService\Config;
use AdminService\Request;
use AdminService\Exception;
use AdminService\model\Token;

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
            return json(-1,'cdk不能为空');
        if(empty($qq))
            return json(-1,'qq不能为空');
        if(empty($price))
            return json(-1,'价格不能为空');
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

    public function test() {
        $cdk=$this->param('cdk','');
        $Mall=new Mall();
        $Mall->getInfoByCdkey($cdk);
        return json(null,null,$Mall->getNewCdkeyByCdkey($cdk));
    }

    public function info() {
        $product_uuid=$this->param('product_uuid','');
        $uuid=$this->param('uuid','');
        $token=$this->param('token','');
        // 判断用户是否登录
        try {
            $Token=new Token();
            $token_info=$Token->getTokenInfo($token);
            if($token_info['uuid']!=$uuid)
                throw new Exception('用户信息与token不匹配');
        } catch (Exception) {
            $uuid='';
        }
        try {
            $Mall=new Mall();
            $result=$Mall->getInfo($product_uuid);
            return json(1,'获取成功',array(
                'product_uuid'=>$product_uuid,
                'product_name'=>$result['product_name'],
                'product_code'=>$result['product_code'],
                'price'=>$result['price'],
                'img'=>$result['img'],
                'is_user'=>$result['uuid']===$uuid,
            ));
        } catch (Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

    public function take_off() {
        $product_uuid=$this->param('product_uuid','');
        $uuid=$this->param('uuid','');
        $token=$this->param('token','');
        $sign=$this->param('sign','');
        // 验证签名
        $data=array(
            'product_uuid'=>$product_uuid,
            'uuid'=>$uuid,
            'token'=>$token
        );
        $server_sign=sign($data);
        if($server_sign!=$sign)
            return json(-1,'签名错误');
        // 判断用户是否登录
        try {
            $Token=new Token();
            $token_info=$Token->getTokenInfo($token);
            if($token_info['uuid']!=$uuid)
                throw new Exception('用户信息与token不匹配');
            // 尝试下架商品
            $Mall=new Mall();
            $Mall->takeOff($product_uuid,$uuid);
            return json(1,'下架成功');
        } catch (Exception $e) {
            return json(-1,$e->getMessage());
        }
    }

    public function change_price() {
        $product_uuid=$this->param('product_uuid','');
        $uuid=$this->param('uuid','');
        $token=$this->param('token','');
        $price=$this->param('price','');
        $sign=$this->param('sign','');
        // 验证签名
        $data=array(
            'product_uuid'=>$product_uuid,
            'uuid'=>$uuid,
            'token'=>$token,
            'price'=>$price
        );
        $server_sign=sign($data);
        if($server_sign!=$sign)
            return json(-1,'签名错误');
        // 判断用户是否登录
        try {
            $Token=new Token();
            $token_info=$Token->getTokenInfo($token);
            if($token_info['uuid']!=$uuid)
                throw new Exception('用户信息与token不匹配');
            // 尝试修改价格
            $Mall=new Mall();
            $result=$Mall->changePrice($product_uuid,$uuid,$price);
            return json(1,'修改成功',array(
                'product_uuid'=>$product_uuid,
                'price'=>$result['price'],
            ));
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

    public function buy() {
        // 这里比较敏感, 只信任固定来源的请求
        $Request=new Request();
        $product_uuid=$Request->getPost('product_uuid','');
        $uuid=$Request->getCookie('uuid','');
        $token=$Request->getCookie('token','');
        $sign=$Request->getPost('sign','');
        // 获取商品信息
        $price=0;
        $code='';
        $name='';
        try {
            $info=$this->info()['data'];
            $price=$info['price'];
            $code=$info['product_code'];
            $name=$info['product_name'];
        } catch (Exception $e) {
            return json(-1,$e->getMessage());
        }
        // 验证参数
        $data=array(
            'product_uuid'=>$product_uuid,
            'uuid'=>$uuid,
            'token'=>$token,
            'price'=>$price,
            'product_code'=>$code,
            'product_name'=>$name,
        );
        $server_sign=sign($data);
        if($server_sign!=$sign)
            return json(-1,'商品信息已修改,请刷新网页',array());
        try {
            $Mall=new Mall();
            $result=$Mall->buy($product_uuid,$uuid,$token);
            return json(1,'购买成功',array(
                'cdkey'=>$result
            ));
        } catch (Exception $e) {
            return json(-1,$e->getMessage());
        }

    }
}

?>
