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

return [
    // 微信开放平台接口
    'service_url' => 'https://demo.thinkadmin.top',
    'wei' => [  //微信公众号参数
        'appid' => '',
        'appsecret' => '',
    ],
    'appAppid' => '', //移动应用appid
    'pcAppid' => '',  //网页应用appid
    'h5Appid' => '',  //微信公众号appid
    'pay'     => [  //微信支付参数
        'appid'      => '',   //默认为移动应用appid
        'mch_id'     => '',
        'mch_key'    => '',
        'ssl_p12'    => __DIR__ . '/cert/apiclient_cert.p12',
        'ssl_cer'    => __DIR__ .'/cert/apiclient_cert.pem',
        'ssl_key'    => __DIR__ .'/cert/apiclient_key.pem',
    ],
    'OAuth' => [    //微信授权登录
        'appid' => '',
        'secret' => '',
        'callback' => '', //返回地址
        'qrconnect' => 'https://open.weixin.qq.com/connect/qrconnect',
        'userinfo' => 'https://api.weixin.qq.com/sns/userinfo',
        'access_token_url' => 'https://api.weixin.qq.com/sns/oauth2/access_token',
    ],
];
