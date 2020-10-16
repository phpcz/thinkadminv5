<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20
 * Time: 11:16
 */
namespace app\api\controller;
use app\api\service\CommonService;
use think\Controller;
use think\facade\Response;
use think\exception\HttpResponseException;
/**
 * 接口公共父类
 * Class Base
 */
class Base extends Controller
{
    /**
     * 签名密钥
     */
    const SECRET_kEY = 'R7CEUAbVO8So6B8H04+r4w==';

    /**
     * 错误码
     * @var array
     */
    protected $code = [
        0 => '请求成功！',
        10000 => '操作成功',
        10001 => '签名错误！',
        10002 => '参数验证错误！',
        10003 => '参数缺少！',
        10004 => '数据不存在！',
        10005 => '账号或密码错误！',
        10006 => '验证码错误！',

        20001 => '用户不存在！',
        20002 => '账号已被禁用！',
        20003 => '账号已被注册！',
        20004 => '请先登录！',
        20005 => '您的QQ未绑定账号，请先绑定账号！',
        20006 => '您的微信未绑定账号，请先绑定账号！',
        20007 => '账号密码已失效,请重新登录！',
        20008 => '账号已在其他端登录！',
        20009 => '请输入手机号',
        20010 => '请输入验证码',
        20011 => '投票项错误',
        20012 => '您的积分不足，无法下载',
        20013 => '邀请码错误',

        30001 => '数据库操作失败！',
        30002 => '短信发送失败！',
        30003 => '第三方接口获取失败！',
        30004 => '文件上传失败！',
        30005 => '支付失败！',
        30006 => '退款失败！',

        40001 => '无权操作！',
        40002 => '访问方式错误！',
        40003 => '操作失败！',
        40004 => '频繁操作！',
        40005 => '操作超时！',
        40006 => '系统错误！',
    ];

    /**
     * 签名白名单
     * @var array
     */
    protected $signWhite = [];

    /**
     * 获取的数据
     * @var
     */
    public $param;

    /**
     * 初始化
     * Base constructor.
     */
    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub

        $this->param = $this->request->param();

        //签名过滤
        if (!in_array($this->request->controller(true) . '/' . $this->request->action(true), $this->signWhite)) {
            if (!CommonService::checkSign($this->request->param(), self::SECRET_kEY, true)) {
                $this->returnCode(['code' => 10001]);
            }
        }

    }

    /**
     * 返回封装后的API数据到客户端
     * @param $param
     */
    protected function returnCode($param)
    {
        $code = $param['code'];
        $msg = isset($param['msg']) ? $param['msg'] : $this->code[$code];
        $data = isset($param['data']) ? $param['data'] : [];

        $this->result($data, $code, $msg, 'json');
    }

    /**
     * 返回数据
     * @param string $msg 消息内容
     * @param array $data 返回数据
     */
    protected function apiResponse($code, $msg, $data = [])
    {
        $result = ['code' => (int)$code, 'msg' => $msg, 'time' => time(), 'data' => $data];

        throw new HttpResponseException(Response::create($result, 'json', 200, $this->corsRequestHander()));
    }

    /**
     * Cors Request Header信息
     * @return array
     */
    protected function corsRequestHander()
    {
        return [
            'Access-Control-Allow-Origin' => request()->header('origin', '*'),
            'Access-Control-Allow-Methods' => 'GET,POST,OPTIONS',
            'Access-Control-Allow-Credentials' => "true",
        ];
    }
}