<?php

namespace app\tool\model;

use base\Model;
use AdminService\Exception;
use AdminService\Config;
use app\tool\model\ApplyPlayer;

class ApplyGroup extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='ssd_tool_apply_group';

    /**
     * 新建组
     * 
     * @access public
     * @param string $group_name 组名
     * @param int $group_type 组类型
     * @param string $klei_id 科雷id
     * @param string $name 游戏名
     * @param string $qq QQ号
     * @return void
     * @throws Exception
     */
    public function create(string $group_name,int $group_type,string $klei_id,string $name,string $qq) {
        // 检查组名是否已经存在
        $group_info=$this->where('name',$group_name)->find();
        if($group_info)
            throw new Exception('组名已经存在');
        $guid=\AdminService\common\uuid(true);
        $this->insert(array(
            'name'=>$group_name,
            'public'=>$group_type,
            'guid'=>$guid,
            'create_time'=>time(),
            'count'=>0
        ));
        $this->join($guid,$klei_id,$name,$qq);
    }

    /**
     * 加入组
     * 
     * @access public
     * @param string $group_guid 组GUID
     * @param string $klei_id 科雷id
     * @param string $name 游戏名
     * @param string $qq QQ号
     * @return void
     * @throws Exception
     */
    public function join(string $group_guid,string $klei_id,string $name,string $qq) {
        $max=Config::get('app.config.all.tool.apply.group.max');
        if($group_guid==='') {
            // 寻找空位
            $group_info=$this->where('count',$max,'<')->find();
            if(!$group_info)
                throw new Exception('没有空位');
            $group_guid=$group_info['guid'];
        }
        // 判断是否已经满员
        $info=$this->where('guid',$group_guid)->find();
        if(!$info)
            throw new Exception('组不存在');
        $count=$info['count'];
        if($count>=$max)
            throw new Exception('该组已经满员');
        $player=new ApplyPlayer();
        // 开启事务
        $this->beginTransaction();
        try {
            $player->join($group_guid,$klei_id,$name,$qq);
        } catch(Exception $e) {
            $this->rollBack();
            throw $e;
        }
        $this->where('guid',$group_guid)->update(array(
            'count'=>$count+1
        ));
        $this->commit();
    }

    /**
     * 获取全部组
     * 
     * @access public
     * @return array
     */
    public function getAll() {
        $max=Config::get('app.config.all.tool.apply.group.max');
        $group_list=$this->where('public',1)->where('count',$max,'<')->select();
        return $group_list;
    }

}