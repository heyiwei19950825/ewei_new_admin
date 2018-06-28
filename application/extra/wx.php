<?php
/**
 * 微信配置文件
 * User: heyiw
 * Date: 2017/9/11
 * Time: 22:03
 */

return [
    //  +---------------------------------
    //  微信相关配置
    //  +---------------------------------


    // 微信使用code换取用户openid及session_key的url地址
    'login_url' => "https://api.weixin.qq.com/sns/jscode2session?" .
        "appid=%s&secret=%s&js_code=%s&grant_type=authorization_code",

    //openID
    'user_token' => "https://api.weixin.qq.com/sns/oauth2/access_token?".
        "appid=%s&secret=%s&code=%s&grant_type=authorization_code",

    // 微信获取access_token的url地址
    'access_token_url' => "https://api.weixin.qq.com/cgi-bin/token?".
        "grant_type=client_credential&appid=%s&secret=%s",


    //获取微信用户信息
    'get_info_url' => "https://api.weixin.qq.com/cgi-bin/user/info?".
        "access_token=%s&openid=%s&lang=zh_CN",

    //刷新access_token
    "refresh_token_url"=>"https://api.weixin.qq.com/sns/oauth2/refresh_token?".
        "appid=%s&grant_type=refresh_token&refresh_token=%s",

    //获取微信jsapi_ticket
    'js_ticket'=>'https://api.weixin.qq.com/cgi-bin/ticket/getticket'.
    '?access_token=%s&type=jsapi',
];