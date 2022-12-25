<?php

namespace app\mall\model;

use base\Model;
use AdminService\App;
use AdminService\Exception;
use AdminService\model\User;
use AdminService\model\Money;
use AdminService\model\Token;

use function AdminService\common\sign;
use function AdminService\common\httpPost;

class Mall extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='trading_system_elaina_mall';

    /**
     * 通过cdkey获取商品信息
     * 
     * @access public
     * @param string $cdkey
     * @param bool $lock 是否锁定商品
     * @return array
     * @throws Exception
     */
    public function getInfoByCdkey(string $cdkey,bool $lock=false): array {
        $url=App::getClass('Config')::get('app.config.all.mall.cdkey_verify_url');
        $data=array(
            'cdk'=>$cdkey,
            'lock'=>$lock?'y':'n',
            'token'=>App::getClass('Config')::get('app.config.all.user.key')
        );
        $sign=sign($data);
        $data['token']=$sign;
        $result=httpPost($url,$data,'json');
        $result=json_decode($result,true);
        App::get('Log')->write('{url} | {cdk} | {sign} | {data} | {result}',array(
            'url'=>$url,
            'cdk'=>$cdkey,
            'sign'=>$sign,
            'data'=>$data,
            'result'=>$result
        ));
        if(empty($result))
            throw new Exception('获取商品信息失败');
        if($result['code']!=1)
            throw new Exception($result['msg']);
        return $result;
    }

    /**
     * 通过旧的cdkey换取新的cdkey
     * 
     * @access public
     * @param string $cdkey
     * @return array
     * @throws Exception
     */
    public function getNewCdkeyByCdkey(string $cdkey): array {
        $url=App::getClass('Config')::get('app.config.all.mall.cdkey_replacecdk_url');
        $data=array(
            'cdk'=>$cdkey,
            'token'=>App::getClass('Config')::get('app.config.all.user.key')
        );
        $sign=sign($data);
        $data['token']=$sign;
        $result=httpPost($url,$data,'json');
        $result=json_decode($result,true);
        App::get('Log')->write('{url} | {cdk} | {sign} | {data} | {result}',array(
            'url'=>$url,
            'cdk'=>$cdkey,
            'sign'=>$sign,
            'data'=>$data,
            'result'=>$result
        ));
        if(empty($result))
            throw new Exception('获取新的CDKEY失败');
        if($result['code']!=1)
            throw new Exception($result['msg']);
        return $result;
    }

    /**
     * 解除CDKEY锁定
     * 
     * @access public
     * @param string $cdkey
     * @return array
     * @throws Exception
     */
    public function unlockCdkey(string $cdkey): array {
        $url=App::getClass('Config')::get('app.config.all.mall.cdkey_unlock_url');
        $data=array(
            'cdk'=>$cdkey,
            'token'=>App::getClass('Config')::get('app.config.all.user.key')
        );
        $sign=sign($data);
        $data['token']=$sign;
        $result=httpPost($url,$data,'json');
        $result=json_decode($result,true);
        App::get('Log')->write('{url} | {cdk} | {sign} | {data} | {result}',array(
            'url'=>$url,
            'cdk'=>$cdkey,
            'sign'=>$sign,
            'data'=>$data,
            'result'=>$result
        ));
        if(empty($result))
            throw new Exception('解除CDKEY锁定失败');
        if($result['code']!=1)
            throw new Exception($result['msg']);
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
        $this->unlockCdkey($product['cdkey']);
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
        $product=$this->where('product_uuid',$product_uuid)->where('uuid',$uuid)->where('status',1)->find(array('status','uuid'));
        if(empty($product))
            throw new Exception('商品不存在');
        // 检查价格是否合法
        $price_min=App::getClass('Config')::get('app.config.all.mall.rule.price.min');
        $price_max=App::getClass('Config')::get('app.config.all.mall.rule.price.max');
        if($price<$price_min||$price>$price_max)
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
        if($price<$price_min||$price>$price_max)
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
        $info=$this->getInfoByCdkey($cdkey);
        $info=$info['data'];
        // 判断cdkey是否已经锁定
        if($info['status']!=1)
            throw new Exception('CDKEY状态异常');
        $product_uuid=\AdminService\common\uuid(true);
        // 上架商品
        $this->insert(array(
            'uuid'=>$uuid,
            'cdkey'=>$cdkey,
            'product_uuid'=>$product_uuid,
            'product_name'=>$info['skinname'],
            'product_code'=>$info['skinprefab'],
            'status'=>0,
            'tag'=>$tag,
            'price'=>$price,
            'create_time'=>time()
        ));
        return array(
            'product_uuid'=>$product_uuid,
            'product_name'=>$info['skinname'],
            'product_code'=>$info['skinprefab'],
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
        $product['new_cdkey']=$this->getNewCdkeyByCdkey($product['cdkey'])['data']['cdk'];
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
                $info=$this->getInfoByCdkey($product['cdkey'],true);
                $info=$info['data'];
                if($info['status']==1) {
                    $this->where('product_uuid',$product['product_uuid'])->where('status',0)->update(array(
                        'status'=>1,
                        'update_time'=>time()
                    ));
                } else {
                    throw new Exception('商品状态异常');
                }
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