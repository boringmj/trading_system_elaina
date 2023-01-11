<?php

namespace app\tool\controller;

use base\Controller;
use AdminService\Config;
use AdminService\Exception;
use AdminService\model\Token;
use AdminService\model\User;
use app\elaina\model\User as ElainaUser;
use app\tool\model\ApplyGroup;
use app\tool\model\ApplyPlayer;

use function AdminService\common\json;
use function AdminService\common\view;

class Apply extends Controller {

    public function index() {
        $uuid=$this->param('uuid','');
        $token=$this->param('token','');
        if($uuid===''||$token==='') {
            $this->header('Location','/index/view/login');
            return;
        }
        $Token=new Token();
        $token_info=$Token->getTokenInfo($token);
        if($token_info['uuid']!==$uuid)
            throw new Exception('令牌错误');
        $User=new User();
        $user_info=$User->getUserInfoByUUID($uuid);
        $user_netid=$user_info['username'];
        $ElainaUser=new ElainaUser();
        $elaina_user_info=$ElainaUser->getUserInfoByNetID($user_netid);
        $klei_id=$elaina_user_info['klei_id'];
        $ApplyPlayer=new ApplyPlayer();
        $group_info=$ApplyPlayer->getJoinGroup($klei_id);
        // 获取同组的人
        $group_user=$ApplyPlayer->getMemberByGuid($group_info['guid']??'');
        $group_user_temp='';
        foreach($group_user as $user) {
            $group_user_temp.="<tr>
                <td>".htmlspecialchars($user['name'])."</td>
                <td>".htmlspecialchars($user['qq'])."</td>
            <tr>";
        }
        // 获取全部组
        $ApplyGroup=new ApplyGroup();
        $all_group=$ApplyGroup->getAll();
        $all_group_temp='';
        foreach($all_group as $group) {
            $all_group_temp.="<tr>
                <td>".htmlspecialchars($group['name'])."</td>
                <td>{$group['count']}</td>
                <td><a href='/tool/apply/admin/goto/join_group/group_id/{$group['guid']}'>加入</a></td>
            <tr>";
        }
        return view('index',array(
            'title'=>'报名申请',
            'nickname'=>htmlspecialchars($user_info['nickname']),
            'group_name'=>htmlspecialchars($group_info['name']??'暂未加入'),
            'all_group'=>$all_group_temp,
            'group_user'=>$group_user_temp,
            'guid'=>$group_info['guid']??'暂无',
        ));
    }

    public function admin() {
        $goto=$this->param('goto','');
        $uuid=$this->param('uuid','');
        $token=$this->param('token','');
        if($uuid===''||$token==='')
            $this->header('Location','/index/view/login');
        try {
            $Token=new Token();
            $token_info=$Token->getTokenInfo($token);
            if($token_info['uuid']!==$uuid)
                throw new Exception('令牌错误');
            $User=new User();
            $user_info=$User->getUserInfoByUUID($uuid);
            $user_netid=$user_info['username'];
            $ElainaUser=new ElainaUser();
            $elaina_user_info=$ElainaUser->getUserInfoByNetID($user_netid);
            $klei_id=$elaina_user_info['klei_id'];
            switch($goto) {
                case "create_group":
                    return view('create_group',array(   
                        'title'=>'创建队伍',
                        'max'=>Config::get('app.config.all.tool.apply.group.max'),
                        'public'=>Config::get('app.config.all.tool.apply.group.public')?'允许':'不允许',
                        'private'=>Config::get('app.config.all.tool.apply.group.private')?'允许':'不允许'
                    ));
                case "leave_group":
                    $ApplyPlayer=new ApplyPlayer();
                    $ApplyPlayer->leaveGroup($klei_id);
                    $this->header('Location','/tool/apply');
                    break;
                case "join_group":
                    $group_id=$this->param('group_id','');
                    $ApplyGroup=new ApplyGroup();
                    $nickname=$elaina_user_info['current_name'];
                    $qq=$user_info['qq'];
                    $ApplyGroup->join($group_id,$klei_id,$nickname,$qq);
                    $this->header('Location','/tool/apply');
                    return json(1,'加入成功');
                    break;
                case "index":
                    $this->header('Location','/tool/apply');
                    break;
            }
        } catch(Exception $e) {
            return json(0,$e->getMessage());
        }
    }

    public function create_group() {
        $group_name=$this->param('group_name','');
        $group_type=$this->param('group_type','');
        $uuid=$this->param('uuid','');
        $token=$this->param('token','');
        try {
            if($uuid===''||$token==='')
                throw new Exception('请先登录');
            if($group_name===''||!in_array($group_type,array('public','private')))
                throw new Exception('参数错误');
            // 判断是否允许创建对应类型的队伍
            if($group_type==='public'&&!Config::get('app.config.all.tool.apply.group.public'))
                throw new Exception('不允许创建公开队伍');
            if($group_type==='private'&&!Config::get('app.config.all.tool.apply.group.private'))
                throw new Exception('不允许创建私有队伍');
            $Token=new Token();
            $token_info=$Token->getTokenInfo($token);
            if($token_info['uuid']!==$uuid)
                throw new Exception('令牌错误');
            $User=new User();
            $user_info=$User->getUserInfoByUUID($uuid);
            $user_netid=$user_info['username'];
            $qq=$user_info['qq'];
            $ElainaUser=new ElainaUser();
            $elaina_user_info=$ElainaUser->getUserInfoByNetID($user_netid);
            $klei_id=$elaina_user_info['klei_id'];
            $nickname=$elaina_user_info['current_name'];
            $group_type=$group_type==='public'?1:0;
            $group=new ApplyGroup();
            $group->create($group_name,$group_type,$klei_id,$nickname,$qq);
            return json(1,'创建成功');
        } catch(Exception $e) {
            return json(0,$e->getMessage());
        }
    }

}

?>
