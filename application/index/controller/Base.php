<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/1
 * Time: 16:06
 */
namespace app\index\controller;
use app\common\model\StudyRotation;
use think\Controller;
use think\Db;

/**
 * 公共父类
 * Class Base
 * @package app\index\controller
 */
class Base extends Controller
{
    protected $param;
    protected $user;

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub

        //操作白名单
        $white_list = [
        ];

        $this->param = $this->request->param();

        if (!session('userinfo') && !$this->checknode($white_list,$this->request->module(),$this->request->controller(),$this->request->action())) {  //操作过滤
            $this->redirect('index/login/index');
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