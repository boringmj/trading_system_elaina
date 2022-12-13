<?php

namespace app\index\controller;

use base\Controller;

use function AdminService\common\view;

class Index extends Controller {

    public function index() {
        $this->header('Location','/index/view');
        return 'Hello World!';
    }

}

?>
