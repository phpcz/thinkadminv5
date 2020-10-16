<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/13
 * Time: 16:11
 */

namespace app\api\service;

use app\common\service\SmsService;
use Endroid\QrCode\QrCode;
use think\Db;
use think\Validate;

/**
 * 支付服务类
 * Class SmsService
 * @package app\common\service
 */
class PayService
{
    /**
     * 支付创建
     * @param $param 参数
     * @param $user 用户信息
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public static function pay($param,$user)
    {

        $type = 0;  //支付方式 按业务分配    1微信 2支付宝
        $trade  = '';   //商户订单号
        $device = 0;    //客户端类型 1、app端 2、pc端 3、h5支付
        $money = 0; //支付金额 单位元
        $body = ''; //商品描述

        if ($type == 1) //微信支付
        {
            if ($device == 1)   //app支付
            {
                $options = [
                    'body' => $body,    //商品描述
                    'out_trade_no' => $trade,   //订单号
                    'total_fee' => $money * 100,      //支付价格
                    'trade_type' => 'APP',    //支付类型
                    'notify_url' => url('/api/notify/weichat', '', true, true),  //通知地址
                    'spbill_create_ip' => request()->ip(),  //终端IP
                ];

                $wechat = new \WeChat\Pay(config('wechat.pay'));

                $result = $wechat->createOrder($options);

                $res = $wechat->createParamsForApp($result['prepay_id']);

                return ['code' => 0, 'msg' => '获取微信支付参数成功！' , 'data' => $res];

            }elseif($device == 2){              //pc支付
                //微信二维码支付
                return self::orderWeiPcPay($trade, $money,$body);
            }else{                              //h5支付
                $options = [
                    'body' => $body,    //商品描述
                    'out_trade_no' => $trade,   //订单号
                    'total_fee' => $money * 100,      //支付价格
                    'trade_type' => 'MWEB',    //支付类型
                    'notify_url' => url('/api/notify/weichat', '', true, true),  //通知地址
                    'spbill_create_ip' => request()->ip(),  //终端IP
                ];

                $wechat = new \WeChat\Pay(config('wechat.pay'));

                $result = $wechat->createOrder($options);

                if ($result['return_code'] == 'SUCCESS' && $result['return_msg'] == 'OK')
                {
                    $h5 = Db::name('study_block')->where('id',4)->value('content');
                    $redirect_url = urlencode($h5.'/user/moneysuccess');
                    return ['code' => 0, 'msg' => '获取微信支付参数成功！' , 'data' => ['mweb_url' => $result['mweb_url'].'&redirect_url='.$redirect_url]];
                }
            }
        }elseif ($type == 2)
        {
            if ($device == 1)   //app支付
            {
                //支付宝APP支付
                $pay = \We::AliPayApp(config('alipay.app'));

                $res = $pay->apply([
                    'out_trade_no' => $trade, // 商户订单号
                    'total_amount' => $money, // 支付金额
                    'subject'      => $body, // 支付订单描述
                ]);

                return ['code' => 0, 'info' => '获取支付宝支付参数成功！' , 'data' => $res];

            }elseif($device == 2){          //pc支付
                //支付宝支付
                $config = config('alipay.pc');
                $config['return_url'] = url('index/user/detail','',true,true);    //回调地址

                $pay = \We::AliPayWeb($config);

                $res = $pay->apply([
                    'out_trade_no' => $trade, // 商户订单号
                    'total_amount' => $money, // 支付金额
                    'subject'      => $body, // 支付订单描述
                ]);

                return ['code' => 0 , 'data' => $res];
            }else{
                //支付宝支付
                $config = config('alipay.pc');
                $config['return_url'] = urlencode('506wap.bjsaiya.cn/user/moneysuccess');    //回调地址

                $pay = \We::AliPayWap($config);

                $res = $pay->apply([
                    'out_trade_no' => $trade, // 商户订单号
                    'total_amount' => $money, // 支付金额
                    'subject'      => $body, // 支付订单描述
                ]);

                return ['code' => 0 , 'data' => $res];
            }
        }
    }

    /**
     * 创建微信支付二维码
     * @param $trade    商户订单号
     * @param $price    金额
     * @param string $body 描述
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public static function orderWeiPcPay($trade, $price, $body = '')
    {
        $wechat = new \WeChat\Pay(config('wechat.pay'));

        $options = [
            'body' => $body,    //商品描述
            'out_trade_no' => $trade,   //订单号
            'total_fee' => $price * 100,      //支付价格
            'trade_type' => 'NATIVE',    //支付类型
            'notify_url' => url('/api/notify/weichat', '', true, true),  //通知地址
            'spbill_create_ip' => request()->ip(),  //终端IP
        ];

        // 生成预支付码
        $result = $wechat->createOrder($options);

        return ['code' => 0 , 'data' => $result['code_url']];

    }

    /**
     * 显示微信支付二维码
     * @param string $url
     * @return \think\Response
     * @throws \Endroid\QrCode\Exceptions\ImageFunctionFailedException
     * @throws \Endroid\QrCode\Exceptions\ImageFunctionUnknownException
     * @throws \Endroid\QrCode\Exceptions\ImageTypeInvalidException
     */
    public static function createQrc($url)
    {
        $qrCode = new QrCode();
        $qrCode->setText($url)->setSize(300)->setPadding(20)->setImageType(QrCode::IMAGE_TYPE_PNG);
        return \think\facade\Response::header('Content-Type', 'image/png')->data($qrCode->get());
    }

    /**
     * IOS内购 示例
     * @param $param
     * @param $user
     * @return array
     */
    public static function IOSPay($param,$user){
        $validate = new Validate([
            'receipt_data|ios验证参数' => 'require',
            'order_no|订单号' => 'require'
        ]);

        if (!$validate->check($param))
        {
            return ['code' => 10002 , 'msg' => $validate->getError()];
        }

        Db::startTrans();
        try {
            $recharge = Db::name('study_recharge')->where(['order_no' => $param['order_no'] , 'user_id' => $user['id']])
                ->lock(true)->find();

            if (!$recharge)
            {
                return ['code' => 10002, 'msg' => '订单不存在！'];
            }

            //判断是否已经支付成功
            if ($recharge['status'] == 1)
            {
                return ['code' => 40003, 'msg' => '充值已经成功，请勿重复支付！'];
            }

            $receiptData = $param['receipt_data'];

            // 验证参数
            if (strlen($receiptData) < 1000) {
                return ['code' => 10002 , 'msg' => '参数验证错误！'];
            }

            // 请求验证【默认向真实环境发请求】
            $html = self::acurl($receiptData);
            $data = json_decode($html, true);//接收苹果系统返回数据并转换为数组，以便后续处理

            // 如果是沙盒数据 则验证沙盒模式
            if ($data['status'] == '21007') {
                // 请求验证  1代表向沙箱环境url发送验证请求
                $html = self::acurl($receiptData, 1);
                $data = json_decode($html, true);
            }

            if (isset($_GET['debug'])) {
                exit(json_encode($data));
            }

            // 判断是否购买成功  【状态码,0为成功（无论是沙箱环境还是正式环境只要数据正确status都会是：0）】
            if (intval($data['status']) === 0) {

                //判断充值金额是否匹配
                $iosRecharge = Db::name('study_ios_recharge')->where(['goods_id' => $data['receipt']['in_app'][0]['product_id']])->find();

                if ($iosRecharge['money'] != $recharge['money']) {
                    return ['code' => 30005, 'msg' => '充值金额不匹配，充值失败！'];
                }

                $resData = self::paySuccess($param['order_no'], $recharge['money'] * 0.7, $data['receipt']['in_app'][0]['transaction_id']);

                if ($resData['code'] != 0) {
                    Db::rollback();
                    return $resData;
                }

                Db::commit();
                return $resData;
            } else {
                Db::rollback();
                return ['code' => 30005, 'msg' => '购买失败,status:' . $data['status']];
            }
        }catch (\Exception $exception)
        {
            Db::rollback();
            return ['code' => 30005 , 'msg' => $exception->getMessage() , 'data' => $data];
        }

    }

    //curl【模拟http请求】
    public static function acurl($receiptData, $sandbox = 0)
    {
        //小票信息
        $POSTFIELDS = array("receipt-data" => $receiptData);
        $POSTFIELDS = json_encode($POSTFIELDS);
        //正式购买地址 沙盒购买地址
        $urlBuy = "https://buy.itunes.apple.com/verifyReceipt";
        $urlSandbox = "https://sandbox.itunes.apple.com/verifyReceipt";
        $url = $sandbox ? $urlSandbox : $urlBuy;//向正式环境url发送请求(默认)
        //简单的curl
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}