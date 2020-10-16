<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 重庆乐湛科技有限公司
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace app\demo\controller;
use library\Controller;

/**
 * 后台页面样式
 * Class AdminStyle
 * @package app\admin\controller
 */
class AdminStyle extends Controller
{

    /**
     * 后台页面样式
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 新页打开
     */
    public function open()
    {
        $this->title = '新页面模板';
        return $this->fetch();
    }

    /**
     * 弹窗打开
     */
    public function modal()
    {
        return $this->fetch();
    }

}
