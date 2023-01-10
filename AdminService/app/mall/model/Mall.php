<?php

namespace app\mall\model;

use AdminService\Log;
use app\elaina\model\Cdkey;
use base\Model;
use AdminService\App;
use AdminService\Exception;
use AdminService\model\User;
use AdminService\model\Money;
use AdminService\model\Token;



class Mall extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='ssd_mall';


    /**
     * 通过旧的cdkey换取新的cdkey
     * 
     * @access public
     * @param string $cdkey
     * @return array
     * @throws Exception
     */
    public function getNewCdkeyByCdkey(string $cdkey): string {
        $Cdkey = new Cdkey();
        $result = $Cdkey->getNewCdkeyByCdkey($cdkey);
        return $result;
    }


    /**
     * 下架一个商品
     * 
     * @access public
     * @param string $product_uuid 商品uuid
     * @param string $uuid 用户uuid
     * @return void
     * @throws Exception
     */
    public function takeOff(string $product_uuid,string $uuid): void {
        $product=$this->where('product_uuid',$product_uuid)->where('uuid',$uuid)->where('status',1)->find(array('status','uuid','cdkey'));
        if(empty($product))
            throw new Exception('商品不存在');
        // 解除CDKEY锁定
        $Cdkey = new Cdkey();
        $Cdkey->unlockCdk($product['cdkey']);
        $this->where('product_uuid',$product_uuid)->update(array(
            'status'=>4,
            'update_time'=>time()
        ));
    }

    /**
     * 修改商品价格
     * 
     * @access public
     * @param string $product_uuid 商品uuid
     * @param string $uuid 用户uuid
     * @param float $price 价格
     * @return array
     * @throws Exception
     */
    public function changePrice(string $product_uuid,string $uuid,float $price): array {
        $product=$this->where('product_uuid',$product_uuid)->where('status',1)->find(array('status','uuid'));
        if(empty($product))
            throw new Exception('商品不存在');
        // 判断是否拥有编辑权限
        $admin_edit=App::getClass('Config')::get('app.config.all.mall.privilege.'.$uuid.'.admin.edit',false);
        if(($product['uuid']!==$uuid)&&!$admin_edit)
            throw new Exception('权限不足');
        // 检查价格是否合法
        $price_min=App::getClass('Config')::get('app.config.all.mall.rule.price.min');
        $price_max=App::getClass('Config')::get('app.config.all.mall.rule.price.max');
        $privilege_unlimited_price=App::getClass('Config')::get('app.config.all.mall.privilege.'.$uuid.'.unlimited_price',false);
        if(($price<$price_min||$price>$price_max)&&!$privilege_unlimited_price)
            throw new Exception('价格不合法');
        // 保留两位小数
        $price=round($price,2);
        $this->where('product_uuid',$product_uuid)->update(array(
            'price'=>$price,
            'update_time'=>time()
        ));
        return array(
            'price'=>$price
        );
    }

    /**
     * 上架一个商品
     * 
     * @access public
     * @param string $cdkey cdkey
     * @param string $qq qq号
     * @param float $price 价格
     * @param string $tag 标签
     * @return array
     * @throws Exception
     */
    public function putOnByQq(string $cdkey,string $qq,float $price,string $tag=''): array {
        // 检查价格是否合法
        $price_min=App::getClass('Config')::get('app.config.all.mall.rule.price.min');
        $price_max=App::getClass('Config')::get('app.config.all.mall.rule.price.max');
        $uuid=$privilege_unlimited_price=App::getClass('Config')::get('app.config.all.mall.privilege.qq.'.$qq,$qq);
        $privilege_unlimited_price=App::getClass('Config')::get('app.config.all.mall.privilege.'.$uuid.'.unlimited_price',false);
        if(($price<$price_min||$price>$price_max)&&!$privilege_unlimited_price)
            throw new Exception('价格不合法');
        // 保留两位小数
        $price=round($price,2);
        // 检查用户真实性
        $user=new User();
        $user=$user->where('qq',$qq)->find(array('uuid','status'));
        if(empty($user))
            throw new Exception('用户不存在');
        if($user['status']!=1)
            throw new Exception('用户状态异常');
        $uuid=$user['uuid'];
        // 获取商品信息
        $sp = $this->where('cdkey', $cdkey)->where('status',2,'<')->find();
        if(!empty($sp)){
            throw new Exception('重复上架');
        }
        $Cdkey = new Cdkey();
        $info = $Cdkey->getCdkInfo($cdkey);
        // 判断cdkey是否已经锁定
        if($info['cdk']['lock']===1)
            throw new Exception('CDKEY已被锁定');
        if($info['cdk']['type'] != 1){
            throw new Exception('该cdk不能上架商店');
        }
        $exp_time = strtotime($info['cdk']['create_time']) + $info['cdk']['cdk_expire_time'] * 24 * 60 * 60;
        if(time() > $exp_time){
            throw new Exception('该cdk已过期');
        }
        foreach ($info['info'] as $key => $value) {
            if($value['skin_expire_time'] != -1){
                throw new Exception('cdk中包含限时皮肤,不能上架');
            }
        }
        $product_uuid=\AdminService\common\uuid(true);
        // 上架商品
        $privilege_quick=App::getClass('Config')::get('app.config.all.mall.privilege.'.$uuid.'.quick',false);
        $skins = "" ;
        if(count($info['info']) > 1){
            foreach ($info['info'] as $key => $value) {
                $skins .= "," . $value['skinname'];
            }
        }
        if($tag === ''){
            $tag = substr($skins, 1);
        }else{
            $tag .= $skins;
        }
        $this->insert(array(
            'uuid'=>$uuid,
            'cdkey'=>$cdkey,
            'product_uuid'=>$product_uuid,
            'product_name'=>$info['info'][0]['skinname'],
            'product_code'=>$info['info'][0]['skinprefab'],
            'status'=>0,
            'tag'=>$tag,
            'price'=>$price,
            'create_time'=>($privilege_quick?time()-600:time()),
        ));
        return array(
            'product_uuid'=>$product_uuid,
            'product_name'=>$info['info'][0]['skinname'],
            'product_code'=>$info['info'][0]['skinprefab'],
            'price'=>$price
        );
    }

    /**
     * 获取商品列表
     * 
     * @access public
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     * @throws Exception
     */
    public function getList(int $page,int $limit): array {
        $page=$page<1?1:$page;
        $limit=$limit<1?1:$limit;
        $offset=($page-1)*$limit;
        $sql="SELECT `product_uuid`,`product_name`,`product_code`,`price`,`create_time`,`tag` FROM `{$this->table_name}` WHERE `status`=1 ORDER BY `priority` DESC,`price` ASC,`id` DESC LIMIT ?,?";
        try {
            $db=$this->getDb();
            // 执行查询
            $stm=$db->prepare($sql);
            $stm->bindValue(1,$offset,\PDO::PARAM_INT);
            $stm->bindValue(2,$limit,\PDO::PARAM_INT);
            $stm->execute();
            $product_array=$stm->fetchAll(\PDO::FETCH_ASSOC);
            foreach($product_array as &$product) {
                // 判断商品代码对应的图片是否存在
                    if(is_file(App::getClass('Config')::get('app.path').'/../../public/img/'.$product['product_code'].'.png'))
                    $product['img']='/img'.'/'.$product['product_code'].'.png';
                else
                    $product['img']='/img/icon.png';
                // 防止xss攻击
                $product['product_name']=htmlspecialchars($product['product_name']??'');
                $product['product_code']=htmlspecialchars($product['product_code']??'');
                $product['tag']=htmlspecialchars($product['tag']??'');
                // 保留两位小数
                $product['price']=round($product['price'],2);
                // 将tag转换为数组
                if(empty($product['tag']))
                    $product['tag']=array();
                else
                    $product['tag']=explode(',',$product['tag']);
            }
            return $product_array;
        } catch(\PDOException $e) {
            throw new Exception("数据库查询错误",0,array(
                'sql'=>$sql,
                'error'=>$e->getMessage()
            ));
        }
    }

    /**
     * 获取商品信息
     * 
     * @access public
     * @param string $product_uuid 商品uuid
     * @return array
     * @throws Exception
     */
    public function getInfo(string $product_uuid): array {
        $product=$this->where('product_uuid',$product_uuid)->where('status',1)->find(array('uuid','cdkey','product_name','product_code','price','create_time','tag'));
        if(empty($product))
            throw new Exception('商品不存在');
        // 判断商品代码对应的图片是否存在
        if(is_file(App::getClass('Config')::get('app.path').'/../../public/img/'.$product['product_code'].'.png'))
            $product['img']='/img'.'/'.$product['product_code'].'.png';
        else
            $product['img']='/img/icon.png';
        // 防止xss攻击
        $product['product_name']=htmlspecialchars($product['product_name']??'');
        $product['product_code']=htmlspecialchars($product['product_code']??'');
        $product['tag']=htmlspecialchars($product['tag']??'');
        // 保留两位小数
        $product['price']=round($product['price'],2);
        // 将tag转换为数组
        if(empty($product['tag']))
            $product['tag']=array();
        else
            $product['tag']=explode(',',$product['tag']);
        return $product;
    }

    /**
     * 购买商品
     * 
     * @access public
     * @param string $product_uuid 商品uuid
     * @param string $uuid 用户uuid
     * @param string $token 用户token
     * @return string
     * @throws Exception
     */
    public function buy(string $product_uuid,string $uuid,string $token): string {
        // 先检查token是否正确
        $Token=new Token();
        $token_info=$Token->getTokenInfo($token);
        if($token_info['uuid']!=$uuid)
            throw new Exception('用户信息错误');
        // 获取商品信息
        $product=$this->getInfo($product_uuid);
        // 通过UUID获取用户余额
        $User=new User();
        $money=$User->getMoney($uuid);
        if($money<$product['price'])
            throw new Exception('余额不足');
        // 获取一个新的cdkey
        $Cdkey = new Cdkey();
        $product['new_cdkey']=$Cdkey->getNewCdkeyByCdkey($product['cdkey']);
        // $product['new_cdkey']=$product['cdkey'];
        $Money=new Money();
        // 开启事务
        $this->beginTransaction();
        $Money->beginTransaction();
        try {
            $bank_uuid=App::getClass('Config')::get('app.config.all.system.uuid.bank');
            $admin_uuid=App::getClass('Config')::get('app.config.all.mall.rule.price.handling_fee_admin_uuid');
            $handling_fee=App::getClass('Config')::get('app.config.all.mall.rule.price.handling_fee');
            // 将用户的钱转移到银行
            $Money->transferByUuid($bank_uuid,$uuid,$product['price'],
                '购买商品:'.$product['product_name'].'|'.$product['new_cdkey']
            );
            // 将手续费转移到管理员
            $currency_name=App::getClass('Config')::get('app.config.all.view.currency_name');
            $handling_fee_admin=App::getClass('Config')::get('app.config.all.mall.rule.price.handling_fee_admin');
            $handling_fee_admin_max=App::getClass('Config')::get('app.config.all.mall.rule.price.handling_fee_admin_max');
            $handling_fee=round($product['price']*$handling_fee,2);
            $handling_fee_admin=min(round($handling_fee*$handling_fee_admin,2),$handling_fee_admin_max);
            $Money->transferByUuid($admin_uuid,$bank_uuid,$handling_fee_admin,
                '交易手续费:'.$product['product_name'].'|'.$product['price'].$currency_name
            );
            // 将扣除手续费后的钱转移到商家
            $Money->transferByUuid($product['uuid'],$bank_uuid,$product['price']-$handling_fee,
                '出售商品:'.$product['product_name'].'|'.$product['price'].'(手续费: '.$handling_fee.$currency_name.')'
            );
            // 修改商品状态
            $this->where('product_uuid',$product_uuid)->update(array(
                'status'=>2,
                'buy_uuid'=>$uuid,
                'update_time'=>time()
            ));
            // 提交事务
            $this->commit();
            $Money->commit();
        } catch(Exception $e) {
            // 回滚事务
            $this->rollBack();
            $Money->rollBack();
            throw $e;
        }
        return $product['new_cdkey'];
    }

    /**
     * 监控
     * 
     * @access public
     * @return void
     * @throws Exception
     */
    public function monitor(): void {
        $product_array=$this->where('status',0)->where('create_time',time()-600,'<')->select(array('product_uuid','cdkey'));
        foreach($product_array as $product) {
            try {
                $Cdkey = new Cdkey();
                $Cdkey->lockCdk($product['cdkey']);
                $this->where('product_uuid',$product['product_uuid'])->where('status',0)->update(array(
                    'status'=>1,
                    'update_time'=>time()
                ));
            } catch(Exception $e) {
                $this->where('product_uuid',$product['product_uuid'])->update(array(
                    'status'=>3,
                    'update_time'=>time()
                ));
                App::get('Log')->write('商品监控出错: {error}',array(
                    'error'=>$e->getMessage()
                ));
                continue;
            }
        }
    }

}