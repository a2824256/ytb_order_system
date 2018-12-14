<?php
/**
 * Created by PhpStorm.
 * User: AlexLeung
 * Date: 2018/11/7
 * Time: 7:51
 */

namespace app\wechat\controller;

use app\wechat\model\BusinessCurrency;
use app\wechat\model\BusinessToOrders;
use app\wechat\model\BusinessToOrdersGoods;
use app\wechat\model\OrderNumber;
use \think\controller\Rest;
use think\Db;
use think\Exception;
use \think\Response;
use \app\common\model\BusinessAccount;
use \app\wechat\model\User;
use \app\business\model\BusinessToGoods;
use \app\business\model\BusinessToGoodsClassifications;
use think\Session;

class Api extends Rest
{
    private $header = ["Access-Control-Allow-Method" => "*", "Access-Control-Allow-Origin" => "http://business.szfengyuecheng.com", "Access-Control-Allow-Credentials" => true, "Access-Control-Allow-Headers" => "Origin, X-Requested-With, Content-Type, Accept"];
//‘    private $header = ["Access-Control-Allow-Method" => "*", "Access-Control-Allow-Origin" => "http://127.0.0.1:8000", "Access-Control-Allow-Credentials" => true, "Access-Control-Allow-Headers" => "Origin, X-Requested-With, Content-Type, Accept"];
    private $output_json_template = [
        'status' => 0
    ];

    /**
     * 兑换表实例
     */
    private $_businessCurrency;

    /**
     * 用户表实例
     */
    private $_user;

    /**
     * 订单号
     */
    private $_orderNumberModel;

    /**
     * 初始化
     */
    public function __construct(BusinessCurrency $businessCurrency, User $user, OrderNumber $orderNumberModel)
    {
        $this->_orderNumberModel = $orderNumberModel;
        $this->_user = $user;
        $this->_businessCurrency = $businessCurrency;
        parent::__construct();
    }

    public function businessList()
    {
        switch ($this->method) {
            case 'get':
                $bussinessList = new BusinessAccount();
                $json['data'] = $bussinessList->field('name,phone,start_hour,start_min,end_hour,end_min,pic,cpc,bid')->select();
                foreach ($json['data'] as $key => $value) {
                    if ($json['data'][$key]['start_hour'] < 10) {
                        $json['data'][$key]['start_hour'] = "0" . $json['data'][$key]['start_hour'];
                    }
                    if ($json['data'][$key]['start_min'] < 10) {
                        $json['data'][$key]['start_min'] = "0" . $json['data'][$key]['start_min'];
                    }
                    if ($json['data'][$key]['end_hour'] < 10) {
                        $json['data'][$key]['end_hour'] = "0" . $json['data'][$key]['end_hour'];
                    }
                    if ($json['data'][$key]['end_min'] < 10) {
                        $json['data'][$key]['end_min'] = "0" . $json['data'][$key]['end_min'];
                    }
                }
                return Response::create($json, 'json', 200, $this->header);
        }
    }

    public function Login()
    {
        switch ($this->method) {
            case 'post':
                $user = new User();
                $json = [];
                $res = $user->where(['telephone' => input('post.telephone'), 'password' => input('post.password')])->field('uid,user_name,address,post_code,telephone')->find();
                if ($res) {
                    $json['status'] = true;
                    $json['user_info'] = $res;
                } else {
                    $json['status'] = false;
                }

//                header('Access-Control-Allow-Origin:*');
//                header('Access-Control-Allow-Methods:*');
//                header('Access-Control-Allow-Headers:*');
//                header('Access-Control-Allow-Credentials:false');
                return Response::create($json, 'json', 200, $this->header);
        }
    }

    public function goodsAndClassList()
    {
        switch ($this->method) {
            case 'get':
                $bussinessList = new BusinessAccount();
                $json['business_info'] = $bussinessList->where(['bid' => input('get.bid')])->field('name,phone,address,start_hour,start_min,end_hour,end_min,pic,cpc,bid')->find();
                if ($json['business_info']['start_hour'] < 10) {
                    $json['business_info']['start_hour'] = "0" . $json['business_info']['start_hour'];
                }
                if ($json['business_info']['start_min'] < 10) {
                    $json['business_info']['start_min'] = "0" . $json['business_info']['start_min'];
                }
                if ($json['business_info']['end_hour'] < 10) {
                    $json['business_info']['end_hour'] = "0" . $json['business_info']['end_hour'];
                }
                if ($json['business_info']['end_min'] < 10) {
                    $json['business_info']['end_min'] = "0" . $json['business_info']['end_min'];
                }
                $goods = new BusinessToGoods();
                $class = new BusinessToGoodsClassifications();
                $goods_list = $goods->where(['bid' => input('get.bid')])->field('name,price,pic,cid,gid')->select();
                $classes = $class->where(['bid' => input('get.bid')])->field('name,cid')->select();
                $json['goods'] = [];
                $json['class_title'] = [];
                foreach ($classes as $key => $value) {
                    $json['class_title'][]['title'] = $value['name'];
                    $json['goods'][$value['cid']] = [];
//                    $json['classes'][$key]['goods'] = [];
                    foreach ($goods_list as $value2) {
                        if ($value2['cid'] == $value['cid']) {
                            $json['goods'][$value['cid']][] = $value2;
                        }
                    }
                }
                $final_json['data'] = $json;

                return Response::create($final_json, 'json', 200, $this->header);
        }
    }

    /**
     * 订单编号
     */
    private function getOrderNumber()
    {
        $rule = '2018';
        $orderNumber = $this->_orderNumberModel->getOrderNumber(1);
        if (strlen($orderNumber->number) < 6) {
            $number = $rule . str_pad((string)$orderNumber->number, 6, '0', STR_PAD_LEFT);
        } else {
            $number = $rule . $orderNumber->number;
        }
        OrderNumber::where(['id'=>1])->setInc("number");
        return $number;
    }

    /**
     * 结算接口
     */
    public function settlement()
    {
        if ($this->method != 'post') {
            return Response::create(['errcode' => -1, 'errmsg' => '错误请求'], 'json', 200, $this->header);
        }
        if(!input('?post.uid')){
            return Response::create(['errcode' => -2, 'errmsg' => '缺失参数'], 'json', 200, $this->header);
        }
        $uid = trim(input('post.uid'));
        $params = [
            'bid' => trim(input('post.bid')),
            'goods' => json_decode(input('post.json'), true),
            'name' => trim(input('post.name')),
            'telephone' => trim(input('post.telephone')),
            'address' => trim(input('post.address')),
            'post_code' => trim(input('post.post_code')),
        ];

        if (!empty($params['goods']) && is_array($params['goods'])) {
            Db::startTrans();
            try {
                //英镑汇率
                $Currency = $this->_businessCurrency->getDetailByFromAndTo('GBP', 'CNY');
                //订单编号
                $orderNumber = $this->getOrderNumber();
                //订单表创建数据
                $User = $this->_user->getInfoByUid($uid);
                $BusinessToOrder = new BusinessToOrders();
                $BusinessToOrder->bid = $params['bid'];
                $BusinessToOrder->order_number = (int)$orderNumber;
                $BusinessToOrder->uid = $User->uid;
                $BusinessToOrder->total_price = $this->caculateTotalPrice($params['goods'])["total_price"];
                $BusinessToOrder->total_price_chy = bcmul($Currency->result, (string)$BusinessToOrder->total_price);
                $BusinessToOrder->exchange_rate = $Currency->result;
                $BusinessToOrder->user_name = $params['name'];
                $BusinessToOrder->user_telephone = $params['telephone'];
                $BusinessToOrder->user_address = $params['address'];
                $BusinessToOrder->user_post_code = $params['post_code'];
                $BusinessToOrder->create_time = date('Y-m-d H:i:s');
                if (!$BusinessToOrder->save()) {
                    throw new \Exception("订单创建失败1");
                }
                //订单记录表
                foreach ($params['goods'] as $gid => $num) {
                    $BusinessToGoods = BusinessToGoods::get($gid);
                    if ($BusinessToGoods) {
                        $BusinessToOrdersGoods = new BusinessToOrdersGoods();
                        $BusinessToOrdersGoods->good_name = $BusinessToGoods->name;
                        $BusinessToOrdersGoods->num = $num;
                        $BusinessToOrdersGoods->price = $BusinessToGoods->price;
                        $BusinessToOrdersGoods->total_price = bcmul($BusinessToGoods->price, $BusinessToOrdersGoods->num);
                        $BusinessToOrdersGoods->order_number = (int)$orderNumber;
                        $BusinessToOrdersGoods->create_time = date('Y-m-d H:i:s');
                        if (!$BusinessToOrdersGoods->save()) {
                            throw new \Exception("订单创建失败2");
                        }
                    }
                }
                Db::commit();
                return Response::create(['errcode' => 0, 'errmsg' => 'ok', 'orderId' => $orderNumber], 'json', 200, $this->header);
            } catch (\Exception $e) {
                Db::rollback();
                return Response::create(['errcode' => -3, 'errmsg' => $e->getMessage(),'line'=>$e->getLine()], 'json', 200, $this->header);
            }
        }else{
            return Response::create(['errcode' => -3, 'errmsg' => $params['goods']], 'json', 200, $this->header);
        }

    }

    public function getUserInfo(){
        switch ($this->method) {
            case 'get':
                $uid = input('get.uid');
                $userInfo = User::where(['uid'=>$uid])->find();
                $final_json = $this->header;
                $final_json['info'] = $userInfo;
                return Response::create($final_json, 'json', 200, $this->header);
        }
    }

    public function getOrders()
    {
        switch ($this->method) {
            case 'get':
                $uid = input('get.uid');
                $final_json['status'] = 1;
                $order_goods = [];
                $orders = BusinessToOrders::where(['uid'=>$uid,'status'=>1])
                    ->join('business_to_orders_goods','business_to_orders_goods.order_number = business_to_orders.order_number')
                    ->field('business_to_orders.order_number,business_to_orders.total_price as order_total_price,business_to_orders.create_time,business_to_orders_goods.good_name,business_to_orders_goods.num,business_to_orders_goods.price,business_to_orders_goods.total_price as good_total_price')
                    ->select()
                    ->toArray();
                foreach($orders as $key =>$value){
                    if(array_key_exists($value['order_number'],$order_goods)){
                        //存在多条账单时处理逻辑
                        $data = [
                            'good_name' => $value['good_name'],
                            'num' => $value['num'],
                            'price' => $value['price'],
                            'good_total_price' => $value['good_total_price'],
                        ];
                        $order_goods[$value['order_number']]['goods'][] = $data;
                    }else{
                        //添加第一条订单数据
                        $data = [
                            'order_number' => $value['order_number'],
                            'order_total_price' => $value['order_total_price'],
                            'create_time' => $value['create_time'],
                            'goods' => [
                                [
                                    'good_name' => $value['good_name'],
                                    'num' => $value['num'],
                                    'price' => $value['price'],
                                    'good_total_price' => $value['good_total_price'],
                                ]
                            ]
                        ];
                        $order_goods[$value['order_number']] = $data;
                    }
                }
                $final_json['orders'] = array_values($order_goods);
                return Response::create($final_json, 'json', 200, $this->header);
        }
    }

    public function updateUserInfo(){
        switch ($this->method) {
            case 'post':
                try{
                    $json_arr = json_decode(input('post.json'), true);
                    if(empty($json_arr)){
                        $final_json['status'] = 0;
                        return Response::create($final_json, 'json', 200, $this->header);
                    }
                    $res = User::where('uid',$json_arr['uid'])
                        ->update([
                            'name'=>$json_arr['name'],
                            'telephone'=>$json_arr['telephone'],
                            'address' => $json_arr['address'],
                            'post_code' => $json_arr['post_code']
                        ]);
                    $final_json['status'] = 1;
                    return Response::create($final_json, 'json', 200, $this->header);

                }catch (\think\Exception $e){
                    $final_json['status'] = 0;
                    $final_json['error_mes'] = $e->getMessage();
                    $final_json['json'] = input('post.json');
                    return Response::create($final_json, 'json', 200, $this->header);
                }

        }
    }

    public function getShoppingCartInfo()
    {
        switch ($this->method) {
            case 'get':
            case 'post':
                $json_array = [];
                $final_json = $this->output_json_template;
                if ($this->method == 'get') {
                    $json_array = json_decode(input('get.json'), true);
                    $final_json['reason'] = "xxx1";
                    $final_json['json'] = input('get.json');
                } else {
                    $json_array = json_decode(input('post.json'), true);
                    $final_json['reason'] = "xxx2";
                    $final_json['json'] = $_POST;
                }

                if (empty($json_array)) {
                    return json($final_json, 200, $this->header);
                }
                $temp_array = [];
//                key是商家bid
                foreach ($json_array as $key => $value) {
//                    商品列表
                    $temp_array[$key] = $this->caculateTotalPrice($value, true);
                }
                $final_json['data'] = $temp_array;
                $final_json['status'] = 1;
                return json($final_json, 200, $this->header);
        }
        echo $this->method;
    }

//  type表示是否要返回除总价之外的其他信息,返回数据类型array
    private function caculateTotalPrice($json_array, $type = false)
    {
        $goods_key = [];
//        将商品主键放到一个数组给模型直接获取
        foreach ($json_array as $key => $value) {
            $goods_key[] = $key;
        }
//        获取所有商品信息
        $goods_info = BusinessToGoods::all($goods_key);
        $total_price = 0;
//        返回给前端查的商品list
        $goods_list = [];
        foreach ($goods_info as $key => $value) {
            if (isset($json_array[$value['gid']])) {
                $total_price += $value['price'] * $json_array[$value['gid']];
                if ($type) {
                    $goods_list[$value['gid']] = new \ArrayObject(['value'=>$value['gid'],'label' => $value['name'], 'num' => $json_array[$value['gid']], 'price'=>$value['price']]);
                }
            }
        }
//        结果
        $res['total_price'] = $total_price;
        if ($type) {
            $res['goods_list'] = $goods_list;
        }
        return $res;
    }
}