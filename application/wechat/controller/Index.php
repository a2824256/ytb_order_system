<?php
namespace app\wechat\controller;
use app\wechat\model\User;
use \EasyWeChat\Factory;
use \think\Controller;
use \think\View;
use \think\Session;

class Index extends Controller
{

    /**
     * @var user 用户实例
     */
    private $_user;

    /**
     * 初始化
     */
    public function __construct(User $user)
    {
        $this->_user = $user;
        parent::__construct();
    }

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
        if (!Session::has('wechat_user')) {
//            $_SESSION['target_url'] = '/business/index/index';
            $oauth->redirect()->send();
        }
// 已经登录过
        $user = Session::get('wechat_user');
        //近期授权过，但是数据库未存有用户信息
        $this->saveData($user);
        //用户首页
        header("Location: http://business.szfengyuecheng.com?openid={$user['id']}");
        exit();
    }

    public function wechatOauthCallback(){
        $config = [
            'app_id' => 'wx007296fc9a7d315f',
            'secret' => '50ddb0815ab75971d407f4218222675c',
            'response_type' => 'array',
        ];
        $app = Factory::officialAccount($config);
        $oauth = $app->oauth;

// 获取 OAuth 授权结果用户信息
        $user = $oauth->user();
        Session::set('wechat_user',$user->toArray());
        $this->saveData($user->toArray());

//        $targetUrl = empty($_SESSION['target_url']) ? '/' : $_SESSION['target_url'];
        header("Location: http://business.szfengyuecheng.com?openid={$user->id}");
        exit();
    }

    /**
     * @param $user 微信用户数据
     */
    private function saveData($user){
        $weiXinInfo = $this->_user->getInfoByOpenid($user['id']);
        if(empty($weiXinInfo)){
            $User = new User();
            $User->user_name = $user['nickname'];
            $User->openid = $user['id'];
            $User->headimgurl = $user['avatar'];
            $User->create_time = date('Y-m-d H:i:s');
            $User->save();
        }else{
            $weiXinInfo->user_name = $user['nickname'];
            $weiXinInfo->headimgurl = $user['avatar'];
            $weiXinInfo->save();
        }
    }

}