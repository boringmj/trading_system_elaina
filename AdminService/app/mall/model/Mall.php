<?php

namespace app\mall\model;

use base\Model;
use AdminService\App;
use AdminService\Exception;
use AdminService\model\User;

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
     * @param string $cdkey
     * @return array
     * @throws Exception
     */
    public function getInfoByCdkey(string $cdkey): array {
        $url=App::getClass('Config')::get('app.config.all.mall.cdkey_verify_url');
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
            throw new Exception('获取商品信息失败');
        if($result['code']!=0)
            throw new Exception($result['msg']);
        return $result;
    }

    /**
     * 上架一个商品
     * 
     * @param string $cdkey cdkey
     * @param string $qq qq号
     * @param float $price 价格
     * @return array
     * @throws Exception
     */
    public function putOnByQq(string $cdkey,string $qq,float $price): array {
        // 检查价格是否合法
        if($price<20||$price>100)
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
        $product_uuid=\AdminService\common\uuid(true);
        // 上架商品
        $this->insert(array(
            'uuid'=>$uuid,
            'cdkey'=>$cdkey,
            'product_uuid'=>$product_uuid,
            'product_name'=>$info['skinname'],
            'product_code'=>$info['skinprefab'],
            'status'=>1,
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
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     * @throws Exception
     */
    public function getList(int $page,int $limit): array {
        $page=$page<1?1:$page;
        $limit=$limit<1?1:$limit;
        $offset=($page-1)*$limit;
        $sql="
            ELECT
            `product_uuid`,`product_name`,`product_code`,`price`,`create_time`
            FROM
            `{$this->table_name}` WHERE `status`=1 ORDER BY `create_time` DESC LIMIT {$offset},{$limit}
        ";
        try {
            $db=$this->getDb();
            // 执行查询
            $stm=$db->prepare($sql);
            $stm->execute();
            $bank_info=$stm->fetchAll();
            return $bank_info;
        } catch(\PDOException $e) {
            throw new Exception("数据库查询错误",0,array(
                'sql'=>$sql,
                'error'=>$e->getMessage()
            ));
        }
    }

}