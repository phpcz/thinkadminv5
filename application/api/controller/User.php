<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/21
 * Time: 10:28
 */
namespace app\api\controller;
use think\Db;

/**
 * 用户接口类
 * Class User
 * @package app\api\controller
 */
class User extends Base
{
    /**
     * 访问白名单
     * @var array
     */
    protected $actionWhite = [

    ];

    /**
     * 当前请求参数信息
     * @var
     */
    public $param;

     /**
     * 当前用户id
     * @var int
     */
    public $uid;

     /**
     * 当前用户数据
     * @var array
     */
    public $user;

     /**
     * 初始化
     * Base constructor.
     */
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub

         //登录访问过滤
        if (!empty($this->param['token']) || session('userinfo') || !$this->checknode($this->actionWhite,$this->request->module(),$this->request->controller(),$this->request->action())) {

        }
        
    }

    /**
     *  权限过滤
     * @param $white_list   白名单
     * @param $module
     * @param $controller
     * @param $action
     * @return bool
     */
    protected function checknode($white_list,$module,$controller,$action)
    {

        foreach ($white_list as &$v){
            $v =  strtolower($v);
        }

        $module = strtolower($module);
        $controller = strtolower($controller);
        $action = strtolower($action);

        if (in_array($module,$white_list))
        {
            return true;
        }

        if (in_array($module.'/'.$controller,$white_list))
        {
            return true;
        }

        if (in_array($module.'/'.$controller.'/'.$action,$white_list))
        {
            return true;
        }

        return false;
    }
}