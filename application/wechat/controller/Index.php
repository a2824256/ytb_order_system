<?php
namespace app\wechat\controller;
use app\wechat\model\User;
use \EasyWeChat\Factory;
use \think\Controller;
use \think\View;
use \think\Session;
use \think\Db;
//http://www.szfengyuecheng.com/wechat/index/wechatOauth
//http://www.szfengyuecheng.com/express/index/index
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

    /**
     * 获取微信信息接口
     */
    public function getWechatInfo(){
        $uid = input('get.uid');
        if(!$uid){
            return json(['errcode' => -1,'errmsg' => 'uid is not empty']);
        }
        $user = User::get($uid);
        return json($user);
    }

    /**
     * Oauth2.0微信授权
     */
    public function wechatOauth(){
//        exit('<meta name="viewport" content="width=device-width, initial-scale=1.0,maximum-scale=1.0, user-scalable=no"/>维护中...');
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
        $uid = $this->saveData($user);
        //用户首页
        header("Location: http://business.szfengyuecheng.com?uid={$uid}&rand=".rand(0,9999));
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
        $uid = $this->saveData($user->toArray());

//        $targetUrl = empty($_SESSION['target_url']) ? '/' : $_SESSION['target_url'];
        header("Location: http://business.szfengyuecheng.com?uid={$uid}");
        exit();
    }

    /**
     * @param $user 微信用户数据
     */
    private function saveData($user){
        $weiXinInfo = $this->_user->getInfoByOpenid($user['id']);
        Db::query('set names utf8mb4;');
        if(empty($weiXinInfo)){
            $User = new User();
            $User->user_name = $user['nickname'];
            $User->openid = $user['id'];
            $User->headimgurl = $user['avatar'];
            $User->create_time = date('Y-m-d H:i:s');
            $User->save();
            $uid = $User->getLastInsID();
        }else{
            $weiXinInfo->user_name = $user['nickname'];
            $weiXinInfo->headimgurl = $user['avatar'];
            $weiXinInfo->save();
            $uid = $weiXinInfo['uid'];
        }
        return $uid;
    }

    public function sendTempMsg()
    {
        $config = [
            'app_id' => 'wx007296fc9a7d315f',
            'secret' => '50ddb0815ab75971d407f4218222675c',
            'response_type' => 'array',
        ];
        $app = Factory::officialAccount($config);
        $accessToken = $app->access_token;
//        $token = $accessToken->getToken();
        $res = $app->template_message->send([
            'touser' => 'ohR9-5g9wak0ss7gjPgbfCVvx5tM',
            'template_id' => 'Avn8YCqx4SGAyjdq5gcPmDFpMsx6iNQOtvGm1OOQocE',
            'url' => 'https://www.baidu.com',
            'data' => [
                'first' => '订单外送通知',
                'keyword1' => '123456789',
                'keyword2' => 'Mr. Leung',
                'keyword3' => 'moor lane 5-7',
                'keyword4' => '130',
                'keyword5' => date("Y-m-d H:i:s"),
                'remark' => '取货商家：东馆',
            ],
        ]);
        var_dump($res);
    }
}