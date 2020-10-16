<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/18
 * Time: 18:00
 */
namespace app\common\service;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ServerException;
use AlibabaCloud\Client\Exception\ClientException;
use think\Db;
use think\Validate;

/**
 * 短信服务
 * Class SmsService
 * @package app\api\service
 */
class SmsService
{
    const ALi_ACCESSKEYID = '';
    const ALi_ACCESSSECRET = '';
    const ALi_SIGNNAME = '';

    /**
     * 短信通知
     * @param $phone    手机号
     * @param $templateCode 模板
     * @param $param  array  模板变量
     * @return array
     * @throws ClientException
     */
    public static function message($phone,$templateCode,$param = array())
    {
        $validate = new Validate();

        if (!$validate->regex($phone,'/^1[23456789]\d{9}$/'))
        {
            return ['code' => 10002 , 'msg' => '请输入正确的手机号！'];
        }

        /**
         * 短信接口
         */
        AlibabaCloud::accessKeyClient(self::ALi_ACCESSKEYID,self::ALi_ACCESSSECRET)
            ->regionId('cn-hangzhou')->asDefaultClient();


        $query = [
            'PhoneNumbers' => $phone,
            'SignName' => self::ALi_SIGNNAME,
            'TemplateCode' => $templateCode,
        ];

        if ($param)
        {
            $query['TemplateParam'] = json_encode($param);
        }

        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => $query,
                ])
                ->request();
            $result = $result->toArray();

            if ($result['Code'] == 'OK' && $result['Message'] == 'OK')
            {
                Db::commit();
                return ['code' => 0 , 'msg' => '通知发送成功！'  ];
            }else{
                Db::rollback();
                return ['code' => 10015 , 'msg' => $result['Message'] ];
            }

        } catch (ClientException $e) {
            Db::rollback();
            return ['code' => 10017 , 'msg' => $e->getErrorMessage() . PHP_EOL];
        } catch (ServerException $e) {
            Db::rollback();
            return ['code' => 10017 , 'msg' => $e->getErrorMessage() . PHP_EOL];
        }
    }

    /**
     * 验证码发送
     * @param $phone    手机号
     * @param $type     验证码类型 1登录、注册 2忘记密码 3修改手机 4修改密码
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function send($phone,$type)
    {
        $validate = new Validate();

        if (!$validate->regex($phone,'/^1[3456789]\d{9}$/'))
        {
            return ['code' => 10002 , 'msg' => '请输入正确的手机号！'];
        }

        Db::startTrans();

        //判断手机号是否频繁发送验证码
        $info = Db::name('study_sms')->where(['phone' => $phone])->order('create_at desc')->find();

        if ($info['create_at'] > date('Y-m-d H:i:s',time()-60))
        {
            return ['code' => 10002 , 'msg' => '请勿频繁发送！'];
        }

        //判断同一ip是否频繁发送
        $num = Db::name('study_sms')->where(['ip' => request()->ip()])->where('create_at','>',date('Y-m-d H:i:s',time()-60))->count();

        if ($num > 10)
        {
            return ['code' => 10002 , 'msg' => '同一IP同一时间发送数量到达上限，请稍后再试！'];
        }

        $code = self::getCode(6);

        $res = Db::name('study_sms')->insert([
            'phone' => $phone,
            'type' => $type,
            'code' => $code,
            'ip' => request()->ip(),
        ]);

        if ($res === false)
        {
            Db::rollback();
            return ['code' => 10002 , 'msg' => '验证码发送失败，请稍后再试！'];
        }
        
        /**
         * 验证码接口
         */
         try {
           AlibabaCloud::accessKeyClient(self::ALi_ACCESSKEYID,self::ALi_ACCESSSECRET)
            ->regionId('cn-hangzhou')->asDefaultClient();

            if ($type == 1){
                $TemplateCode = 'SMS_201682379';
            }elseif ($type == 2){
                $TemplateCode = 'SMS_201682379';
            }elseif($type == 3){
                $TemplateCode = 'SMS_201682379';
            }else{
            	$TemplateCode = 'SMS_201682379';
            }

                $result = AlibabaCloud::rpc()
                    ->product('Dysmsapi')
                    // ->scheme('https') // https | http
                    ->version('2017-05-25')
                    ->action('SendSms')
                    ->method('POST')
                    ->host('dysmsapi.aliyuncs.com')
                    ->options([
                        'query' => [
                            'RegionId' => "cn-hangzhou",
                            'PhoneNumbers' => $phone,
                            'SignName' => self::ALi_SIGNNAME,
                            'TemplateCode' => $TemplateCode,
                            'TemplateParam' => json_encode(['code' => $code]),
                        ],
                    ])
                    ->request();
                $result = $result->toArray();


            if ($result['Code'] == 'OK' && $result['Message'] == 'OK')
            {
                Db::commit();
                return ['code' => 0 , 'msg' => '获取验证码成功！' , 'data' => $code ];
            }else{
                Db::rollback();
                return ['code' => 10015 , 'msg' => $result['Message'] , 'data' => $code ];
            }

        } catch (ClientException $e) {
            Db::rollback();
            return ['code' => 10017 , 'msg' => $e->getErrorMessage() . PHP_EOL];
        } catch (ServerException $e) {
            Db::rollback();
            return ['code' => 10017 , 'msg' => $e->getErrorMessage() . PHP_EOL];
        }

    }

    /**
     * 验证码生成
     * @param int $length
     * @return string
     */
    private static function getCode($length = 6)
    {
        $code = '';
        while (strlen($code) < $length) $code .= rand(0,9);
        return $code;
    }

    /**
     * 验证码验证
     * @param $phone    手机号
     * @param $type     验证码类型 验证码类型 1登录、注册 2忘记密码 3修改手机 4修改密码 5支付
     * @param $code     验证码
     * @param int $update    是否更改验证码状态 1更改 0不更改
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function check($phone,$type,$code,$update = 1)
    {
        $info = Db::name('study_sms')->where(['phone' => $phone , 'type' => $type])
            ->where('create_at','>',date('Y-m-d H:i:s',time() - 15 * 60))
            ->order('create_at desc')->find();

        if (!$info)
        {
            return false;
        }

        //是否已经被验证过
        if ($info['check'] != 0)
        {
            return false;
        }

        //ip是否匹配
        if ($info['ip'] != request()->ip())
        {
            return false;
        }

        //验证码是否正确
        if ($info['code'] == $code)
        {
            if ($update == 1) {
                $res = Db::name('study_sms')->where(['id' => $info['id']])->update([
                    'check' => 1,
                    'check_at' => date('Y-m-d H:i:s'),
                ]);

                if ($res !== false)
                {
                    return true;
                }

                return false;
            }
            return true;
        }

        return false;
    }
}