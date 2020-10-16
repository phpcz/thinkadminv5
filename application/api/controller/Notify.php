<?php
namespace app\api\controller;
use app\api\service\PayService;
use think\Controller;
use think\Db;
use WeChat\Pay;

//include './extend/alipay/AopSdk.php';
/**
 * 通知控制器
 * @author lezhan <lezhan100.com>
 */
class Notify extends Controller
{

    /**
     * 支付宝支付回调
     * @return string
     */
    public function alipay()
    {
        try {
            $pay = \We::AliPayApp('支付宝参数信息一般存于config->alipay.php中');
            $data = $pay->notify(); //通知信息
            p($data);   //打印到服务器
            if (in_array($data['trade_status'], ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {

                /**
                 * 成功逻辑成功
                 * $data['out_trade_no'] 商户订单号
                 * $data['buyer_pay_amount'] 支付价格 单位元
                 * $data['trade_no'] 支付订单号
                 */

                return 'SUCCESS';
            } else {
                return 'ERROR';
            }
        }catch (\Exception $e)
        {
            p($e->getMessage());
            return 'ERROR';
        }

    }



    /**
     * 微信支付回调
     * @return string
     */
    public function weichat()
    {
        try {
            $wechat = new Pay(config('wechat.pay'));
            p($wechat->getNotify()); //打印到服务器
            $data = $wechat->getNotify(); //返回信息

            if ($data['result_code'] == 'SUCCESS' && $data['return_code'] == 'SUCCESS') {
                /**
                 * 成功逻辑处理
                 * $data['out_trade_no'] 商户订单号
                 * $data['total_fee'] 支付价格 单位分
                 * $data['transaction_id'] 支付订单号
                 */

                return 'SUCCESS';
           }
           return 'ERROR';
        }catch (\Exception $e)
        {
            p($e->getMessage());
            return 'ERROR';
        }
    }
}
