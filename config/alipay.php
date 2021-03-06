<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2018 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/WeChatDeveloper
// +----------------------------------------------------------------------

return [
    'pc' => [
        // 沙箱模式
        'debug'       => false,
        // 签名类型（RSA|RSA2）
        'sign_type'   => "RSA2",
        // 应用ID
        'appid'       => '',
        // 支付宝公钥(1行填写，特别注意，这里是支付宝公钥，不是应用公钥，最好从开发者中心的网页上去复制)
        'public_key'  => '',
        // 支付宝私钥(1行填写)
        'private_key' => '',
        // 支付成功通知地址
        'notify_url'  => '',
        // 网页支付回跳地址
        'return_url'  => '',
    ],
    'app' => [
        // 沙箱模式
        'debug'       => false,
        // 签名类型（RSA|RSA2）
        'sign_type'   => "RSA2",
        // 应用ID
        'appid'       => '',
        // 支付宝公钥(1行填写，特别注意，这里是支付宝公钥，不是应用公钥，最好从开发者中心的网页上去复制)
        'public_key'  => '',
        // 支付宝私钥(1行填写)
        'private_key' => '',
        // 支付成功通知地址
        'notify_url'  => '',
        // 网页支付回跳地址
        'return_url'  => '',
    ],
];