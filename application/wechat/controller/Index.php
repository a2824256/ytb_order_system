<?php
namespace app\wechat\controller;
use \EasyWeChat\Factory;
use \think\Controller;
use \think\View;
class Index extends Controller
{
    public function index(){
        $view = new View();
        return $view->fetch();
    }
    public function wechatOauth(){
        $config = [
            'app_id' => 'wx007296fc9a7d315f',
            'secret' => '50ddb0815ab75971d407f4218222675c',
            'response_type' => 'array',
            'oauth' => [
                'scopes'   => ['snsapi_userinfo'],
                'callback' => '/wechat/index/wechatOauthCallback',
            ],
        ];

        $app = Factory::officialAccount($config);
        $oauth = $app->oauth;

// 未登录
        if (empty($_SESSION['wechat_user'])) {

            $_SESSION['target_url'] = '/business/index/index';
            $oauth->redirect()->send();
        }

// 已经登录过
        $user = $_SESSION['wechat_user'];
        echo $user;
    }

    public function wechatOauthCallback(){
        $config = [
            'app_id' => 'wx007296fc9a7d315f',
            'secret' => '50ddb0815ab75971d407f4218222675c',
            'response_type' => 'array',
            'oauth' => [
                'scopes'   => ['snsapi_userinfo'],
                'callback' => '/wechat/index/wechatOauthCallback',
            ],
        ];
        $app = Factory::officialAccount($config);
        $oauth = $app->oauth;

// 获取 OAuth 授权结果用户信息
        $user = $oauth->user();

        $_SESSION['wechat_user'] = $user->toArray();

        $targetUrl = empty($_SESSION['target_url']) ? '/' : $_SESSION['target_url'];

        var_dump($user);
    }
}