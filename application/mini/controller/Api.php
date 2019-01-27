<?php

namespace app\mini\controller;

use app\business\model\BusinessToGoodsAttributes;
use \think\controller\Rest;
use \app\common\model\BusinessAccount;
use \app\business\model\BusinessToOrdersGoods;
use \app\business\model\BusinessToGoods;
use \app\business\model\BusinessToGoodsClassifications;
use think\Db;
use think\Exception;
use EasyWeChat\Factory;
use \think\Response;
use \think\Request;

class Api extends Rest
{
    public function __construct()
    {
        parent::__construct();
//        $this->mini_token = 'b2JvekU1QS0xd25RWWNJZ25WSVNNOUluS0dPODIwMTkwMTEwMTQyODIy';
        $action =  \request()->controller() .'/'. \request()->action();
        if (!in_array($action, self::$permitFunction)) {
            $token = Request::instance()->header("mini-token", 0);
            if (!$token) {
                $json = $this->res_template;
                $json['code'] = 403;
                $json['token'] = $token;
                $json['msg'] = '不能token为空';
                die(json_encode($json));
            } else {
                $this->mini_token = $token;
                //验证token是否失效
                $user = $this->getUser();
                if(empty($user)){
                    $json = $this->res_template;
                    $json['code'] = 403;
                    $json['token'] = $token;
                    $json['msg'] = 'token无效';
                    die(json_encode($json));
                }
                if(date('Y-m-d H:i:s') > $user['token_timeout']){
                    $json = $this->res_template;
                    $json['code'] = 403;
                    $json['token'] = $token;
                    $json['msg'] = 'token失效';
                    die(json_encode($json));
                }
            }
        }
    }

    /**
     * 不需要提交token的API
     * 操作名都是小写
     * @var array
     */
    private static $permitFunction = [
        'Api/auth',
        'Index/businesslist',
        'Index/businessinfo',
        'Goods/goods',
        'Goods/search',
        'Evaluation/getlist',
        'Order/getorderstatus',
        'Pay/notify',
    ];

    //token
    protected $mini_token = null;

    //json模板
    private $res_template = [
        'status' => 0,
        'code' => 200,
        'msg' => 0,
    ];

    //code枚举
    private $_code = [
        200 => '操作成功',
        400 => 'token过期',
        401 => '用户不存在',
        402 => '参数不正确',
        403 => 'token不正确',
        404 => 'session_key返回异常',
        405	=> '数据异常',
    ];

    private static $config = [
        'app_id' => 'wxaad5eab0d5cd8ee0',
        'secret' => '2889d3742fcc9a63df0f1d3a48de33da',
        'response_type' => 'array',
    ];

    private static function tokenEncode($code)
    {
//        TODO 待完善
        return base64_encode($code);
    }

    private static function tokenDecode($code)
    {
//        TODO 待完善
        return base64_decode($code);
    }

    private static function generateToken($openid)
    {
        return self::tokenEncode($openid . date("YmdHis"));
    }

    public function auth()
    {
        $json = [
            'status' => 0
        ];
//        检查code是否为空
        $code = input('post.code');
        if (empty($code)) {
            return Response::create($json, 'json', 200);
        }
        $iv = input('post.iv');
        $ed = input('post.ed');
        $app = Factory::miniProgram(self::$config);
        $info = $app->auth->session($code);

        if (empty($iv) || empty($ed)) {
//            两者为空，老用户
            $json = $this->res_template;
            $openid = $info['openid'];
            $user_info = Db::table('user_mini_info')->where(['order_openid' => $openid])->field('token,token_timeout')->find();
            if (empty($user_info)) {
                $json['code'] = 401;
                return Response::create($json, 'json', 200);
            }
            $res = date("Y-m-d H:i:s") > $user_info['token_timeout'];

            if ($res) {
                //超时
                $token = self::generateToken($openid);
                $time = date("Y-m-d H:i:s");
                Db::table('user_mini_info')->where(['order_openid' => $openid])->update([
                    'token' => $token,
                    'token_timeout' => date("Y-m-d H:i:s", strtotime($time) + 7200),
                ]);
                $json['data']['token'] = $token;
            } else {
//                未超时
                $json['data']['token'] = $user_info['token'];
            }
            $json['status'] = 1;
            $json['code'] = 200;
            return Response::create($json, 'json', 200);
        } else if ($iv && $ed) {
            //两者不为空，授权新用户
            try {
                $decryptedData = $app->encryptor->decryptData($info['session_key'], $iv, $ed);
            } catch (Exception $e) {
//            测试代码
//            $json['info'] = $info;
                $json['code'] = 404;
                return Response::create($json, 'json', 200);
            }
            $user_info = Db::table('user_mini_info')->where(['order_openid' => $decryptedData['openId']])->field('token,token_timeout')->find();
            $token = '';
            $time = date("Y-m-d H:i:s");
            if (empty($user_info)) {
                $token = self::generateToken($decryptedData['openId']);
                Db::table('user_mini_info')->insert([
                    'order_openid' => $decryptedData['openId'],
                    'nickname' => $decryptedData['nickName'],
                    'avatarUrl' => $decryptedData['avatarUrl'],
                    'create_time' => $time,
                    'update_time' => $time,
                    'token_timeout' => date("Y-m-d H:i:s", strtotime($time) + 7200),
                    'token' => $token
                ]);
            } else {
                $res = date("Y-m-d H:i:s") > $user_info['token_timeout'];
                if ($res) {
                    //token过期
                    $token = self::generateToken($decryptedData['openId']);
                    Db::table('user_mini_info')->where(['order_openid' => $decryptedData['openId']])->update([
                        'nickname' => $decryptedData['nickName'],
                        'avatarUrl' => $decryptedData['avatarUrl'],
                        'token_timeout' => date("Y-m-d H:i:s", strtotime($time) + 7200),
                        'token' => $token
                    ]);
                } else {
                    //token未过期
                    $token = $user_info['token'];
                }
            }
            $json['status'] = 1;
            $json['data']['token'] = $token;
            $json['code'] = 200;
            return Response::create($json, 'json', 200);
        } else {
            //两者其一不为空，异常
            $json['code'] = 402;
            return Response::create($json, 'json', 200);
        }

    }

    /**
     * 用户信息
     */
    public function getUserInfo()
    {
        $handleList = [];
        $list = Db::table('user_mini_info')->alias('umi')->join('address a', 'umi.uid = a.uid','left')
            ->where(['umi.token' => $this->mini_token])->field('a.is_default,a.address,a.telephone,a.post_code,a.name,a.id as address_id,umi.uid,umi.token,umi.nickname,umi.avatarUrl')->select();
        foreach($list as $k=>$value){
            if(isset($handleList['addressList'])){
                //存在多条数据时处理逻辑
                $handleList['addressList'][] =  [
                    'address' => $value['address'],
                    'telephone' => $value['telephone'],
                    'post_code' => $value['post_code'],
                    'name' => $value['name'],
                    'id' => $value['address_id'],
                    'is_default' => $value['is_default']
                ];
            }else{
                $data[] = [
                    'address' => $value['address'],
                    'telephone' => $value['telephone'],
                    'post_code' => $value['post_code'],
                    'name' => $value['name'],
                    'id' => $value['address_id'],
                    'is_default' => $value['is_default'],
                ];
                $handleList = [
                    'id' => $value['uid'],
                    'token' => $value['token'],
                    'nickname' => $value['nickname'],
                    'avatarUrl' => $value['avatarUrl'],
                    'addressList' => !empty($value['address_id']) ? $data : [],
                ];
            }
        }
        $json = $this->res_template;
        $json['status'] = 1;
        $json['code'] = 200;
        $json['msg'] = 'ok';
        $json['data'] = $handleList;
        return Response::create($json, 'json', 200);
    }

    public function addAddress()
    {
        $name = input('post.name');
        $telephone = input('post.telephone');
        $address = input('post.address');
        $postcode = input('post.post_code');
        if (empty($telephone) || empty($address) || empty($postcode) || empty($name)) {
            $json['code'] = 402;
            return Response::create($json, 'json', 200);
        }
        $user_info = Db::table('user_mini_info')->where(['token' => $this->mini_token])->field('uid')->find();
        $res = Db::table('address')->insert([
            'name' => $name,
            'address' => $address,
            'telephone' => $telephone,
            'post_code' => $postcode,
            'uid' => $user_info['uid'],
        ]);
        $json = $this->res_template;
        if($res){
            $json['status'] = 1;
            $json['code'] = 200;
        }else{
            $json['code'] = 405;
        }
        return Response::create($json, 'json', 200);
    }

    public function updateAddress()
    {
        $aid = input('post.id');
        $name = input('post.name');
        $telephone = input('post.telephone');
        $address = input('post.address');
        $postcode = input('post.post_code');
        if (empty($telephone) || empty($address) || empty($postcode) || empty($name)) {
            $json['code'] = 402;
            return Response::create($json, 'json', 200);
        }
        $res = Db::table('address')->where(['id' => $aid])->update([
            'name' => $name,
            'address' => $address,
            'telephone' => $telephone,
            'post_code' => $postcode,
        ]);
        $json = $this->res_template;
        if($res){
            $json['status'] = 1;
            $json['code'] = 200;
        }else{
            $json['code'] = 405;
        }
        return Response::create($json, 'json', 200);
    }

    /**
     * 删除收货地址
     */
    public function deleteAddress(){
        switch ($this->method){
            case 'post':
                $aid = input('post.id');
                if(empty($aid)){
                    return Response::create(['code' => 402,'status' => 0,'msg' => '缺少参数'], 'json', 200);
                }
                if(Db::table('address')->delete($aid)){
                    return Response::create(['code' => 200,'status' => 1,'msg' => '操作成功'], 'json', 200);
                }else{
                    return Response::create(['code' => 405,'status' => 0,'msg' => '异常报错'], 'json', 200);
                }
        }
    }

    /**
     * 是否默认
     */
    public function setAddressDefault(){
        switch ($this->method){
            case 'post':
                $aid = input('post.id');
                $user = $this->getUser();
                if(empty($user)){
                    return Response::create(['code' => 401,'status' => 0,'msg' => '用户不存在'], 'json', 200);
                }
                if(empty($aid)){
                    return Response::create(['code' => 402,'status' => 0,'msg' => '缺少参数'], 'json', 200);
                }
                if(Db::table('address')->where(['id' => $aid])->update(['is_default' => 1])){
                    Db::table('address')->where(['uid' => $user['uid']])->where('id','<>',$aid)->update(['is_default' => 0]);
                    return Response::create(['code' => 200,'status' => 1,'msg' => '操作成功'], 'json', 200);
                }else{
                    return Response::create(['code' => 405,'status' => 0,'msg' => '异常报错'], 'json', 200);
                }
        }
    }

    /**
     * 获取用户信息（内部）
     */
    protected function getUser(){
        if(empty($this->mini_token)){
            return [];
        }
        $user = Db::table('user_mini_info')->where(['token' => $this->mini_token])->find();
        if(empty($user)){
            return [];
        }
        return $user;
    }

}