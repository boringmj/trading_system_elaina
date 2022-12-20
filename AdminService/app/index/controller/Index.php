<?php

namespace app\index\controller;

use base\Controller;
use AdminService\model\Money as MoneyModel;
use AdminService\Exception;

class Index extends Controller {

    public function index() {
        $this->header('Location','/index/view');
        return 'Hello World!';
    }

    public function a() {
        $data=[
        ];
        // 实例化模型
        $Money=new MoneyModel();
        // 转账
        foreach($data as $v) {
            try {
                $Money->transferByQq($v,'1258706440',10,'22年魔女冬季活动补偿');
            } catch(Exception $e) {
                echo $e->getMessage();
            }
        }
        return "ok!";
    }

    public function b() {
        $data=[
        ];
        // 实例化模型
        $Money=new MoneyModel();
        // 通过id回滚
        foreach($data as $v) {
            try{
                $Money->rollbackById($v,'回滚-22年魔女冬季活动补偿');
            } catch(Exception $e) {
                echo $e->getMessage();
            }
        }
        return "ok!";
    }

}

?>
