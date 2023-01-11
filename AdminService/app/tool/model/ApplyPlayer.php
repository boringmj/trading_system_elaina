<?php

namespace app\tool\model;

use base\Model;
use AdminService\Exception;
use app\tool\model\ApplyGroup;

class ApplyPlayer extends Model {

    /**
     * 数据库表名
     * @var string
     */
    public string $table_name='ssd_tool_apply_player';

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
        // 检查是否已经加入其他组
        $user_info=$this->where('klei_id',$klei_id)->find();
        if($user_info)
            throw new Exception('你已经加入了其他组');
        $this->insert(array(
            'klei_id'=>$klei_id,
            'name'=>$name,
            'qq'=>$qq,
            'guid'=>$group_guid,
            'create_time'=>time()
        ));
    }

    /**
     * 获取加入的组
     * 
     * @access public
     * @param string $klei_id 科雷id
     * @return array
     * @throws Exception
     */
    public function getJoinGroup(string $klei_id) {
        $user_info=$this->where('klei_id',$klei_id)->find();
        if(!$user_info)
            return array();
        $ApplyGroup=new ApplyGroup();
        $group_info=$ApplyGroup->where('guid',$user_info['guid'])->find();
        return $group_info;
    }

    /**
     * 退出当前队伍
     * 
     * @access public
     * @param string $klei_id 科雷id
     * @return void
     * @throws Exception
     */
    public function leaveGroup(string $klei_id) {
        $user_info=$this->where('klei_id',$klei_id)->find();
        if(!$user_info)
            return;
        $this->where('klei_id',$klei_id)->delete();
        $ApplyGroup=new ApplyGroup();
        $count=$ApplyGroup->where('guid',$user_info['guid'])->find('count');
        $ApplyGroup->where('guid',$user_info['guid'])->update(array(
            'count'=>$count-1
        ));
    }

    /**
     * 通过 guid 获取成员
     * 
     * @access public
     * @param string $guid guid
     * @return array
     * @throws Exception
     */
    public function getMemberByGuid(string $guid) {
        $list=$this->where('guid',$guid)->select();
        return $list;
    }

}