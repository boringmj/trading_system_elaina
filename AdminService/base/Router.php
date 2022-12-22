<?php

namespace base;

use base\Request;
use AdminService\Exception;
use AdminService\App;

abstract class Router {

    /**
     * 请求对象
     * @var \base\Request
     */
    protected Request $request;

    /**
     * 路由路径组
     * @var array
     */
    protected array $uri;

    /**
     * 是否已经初始化
     * @var bool
     */
    protected bool $is_init;

    /**
     * 通过路由路径组加载控制器
     * 
     * @access public
     * @param array $route_info 路由路径组
     * @return self
     */
    abstract public function load(array $route_info=array()): self;

     /**
     * 通过路由路径组返回路由信息(调用此方法前请先调用 checkInit() 方法)
     * 
     * @access public
     * @return array
     */
    abstract public function getRouteInfo(): array;

    /**
     * 获取路由路径组
     * 
     * @access private
     * @return array
     */
    private function route(): array {
        $uri=$_SERVER['REQUEST_URI'];
        // 写入本次请求的日志
        $log_class=App::getClass('Log');
        // 日志名称是今天的日期
        $file_name=date('Y-m-d').'-request';
        $log=new $log_class($file_name);
        $log->write('Request URI: {uri} by {ip}({forwarder_ip}) - post: {post} | get: {get} | cookie: {cookie}',array(
            'uri'=>$uri,
            'ip'=>isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'0.0.0.0',
            'forwarder_ip'=>isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:'0.0.0.0',
            'post'=>self::filterPrivacy($_POST),
            'get'=>self::filterPrivacy($_GET),
            'cookie'=>self::filterPrivacy($_COOKIE)
        ));
        $uri=explode("?",$uri);
        $uri=$uri[1]??$uri[0];
        $uri=explode("/",$uri);
        array_shift($uri);
        $uri=array_values($uri);
        return $uri;
    }

    /**
     * 过滤隐私信息
     * 
     * @access private
     * @param array $data 数据
     * @return array
     */
    private static function filterPrivacy(array $data): array {
        $array_list=array('pass','password','passwd','token','key','cdkey','cdk');
        foreach($data as $key=>&$value) {
            if(is_array($value)) {
                $value=self::filterPrivacy($value);
            } else {
                if(in_array($key,$array_list,true))
                    $value='******';
            }
        }
        return $data;
    }

    /**
     * 构造方法(如果都传入则默认初始化)
     * 
     * @access public
     * @param Request $request 请求对象
     */
    final public function __construct(?Request $request=null) {
        $this->is_init=false;
        $this->uri=array();
        return $this->init($request);
    }

    /**
     * 初始化路由
     * 
     * @access public
     * @param Request $request 请求对象
     * @return self
     */
    final public function init(?Request $request=null): self {
        $this->request=$request??App::get('Request');
        $this->uri=$this->route();
        $this->is_init=true;
        return $this;
    }

    /**
     * 获取路由路径组
     * 
     * @access public
     * @return array
     */
    final public function get(): array {
        $this->checkInit();
        return $this->uri;
    }

    /**
     * 检查是否已经初始化
     * 
     * @access protected
     * @return void
     */
    protected function checkInit(): void {
        if(!$this->is_init)
            throw new Exception('Route is not initialized.',-406);
    }

}

?>