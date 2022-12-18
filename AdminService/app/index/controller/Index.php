<?php

namespace app\index\controller;

use base\Controller;
use AdminService\model\Money as MoneyModel;

use function AdminService\common\view;

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
            $Money->transferByQq($v,'1258706440',10,'22年魔女冬季活动补偿');
        }
        return "ok!";
    }

    public function b() {
        $data=[
            1856,
            1857,
            1858,
            1859,
            1860,
            1861,
            1862,
            1863,
            1864,
            1865,
            1866,
            1867,
            1868,
            1869,
            1870,
            1871,
            1872,
            1873,
            1874,
            1875,
            1876,
            1877
        ];
        // 实例化模型
        $Money=new MoneyModel();
        // 通过id回滚
        foreach($data as $v) {
            $Money->rollbackById($v,'回滚-22年魔女冬季活动补偿');
        }
        return "ok!";
    }

}

?>
