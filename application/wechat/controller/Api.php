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
use \think\Response;
use \app\common\model\BusinessAccount;
use \app\wechat\model\User;
use \app\business\model\BusinessToGoods;
use \app\business\model\BusinessToGoodsClassifications;
use think\Session;

class Api extends Rest
{
    private $header = ["Access-Control-Allow-Method" => "*", "Access-Control-Allow-Origin" => "http://127.0.0.1:8000", "Access-Control-Allow-Credentials" => true, "Access-Control-Allow-Headers" => "Origin, X-Requested-With, Content-Type, Accept"];
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
        $rule = '16820181125666';
        $orderNumber = $this->_orderNumberModel->getOrderNumber(1);
        if (strlen($orderNumber->number) < 8) {
            $number = $rule . str_pad((string)$orderNumber->number, 8, '0', STR_PAD_LEFT);
        } else {
            $number = $rule . $orderNumber->number;
        }
        $orderNumber->number += 1;
        $orderNumber->save();
        return $number;
    }

    /**
     * 结算接口
     */
    public function settlement()
    {
        if (!Session::has('wechat_user')) {
            return json(['errcode' => -1, 'errmsg' => 'Loss of authorization information']);
        }

        if ($this->method != 'post') {
            return json(['errcode' => -2, 'errmsg' => 'Incorrect request method']);
        }

        $params = [
            'bid' => trim(input('post.bid')),
            'goods' => json_decode(input('post.goods'), true),
            'name' => trim(input('post.name')),
            'telephone' => trim(input('post.telephone')),
            'address' => trim(input('post.address')),
            'post_code' => trim(input('post.post_code')),
        ];

        if (!empty($params['goods']) && is_array($params['goods'])) {
            try {
                Db::transaction();
                //英镑汇率
                $Currency = $this->_businessCurrency->getDetailByFromAndTo('GBP', 'CNY');
                //订单编号
                $orderNumber = $this->getOrderNumber();
                //订单记录表
                foreach ($params['goods'] as $gid => $num) {
                    $BusinessToGoods = BusinessToGoods::get($gid);
                    if ($BusinessToGoods) {
                        $BusinessToOrdersGoods = new BusinessToOrdersGoods();
                        $BusinessToOrdersGoods->good_name = $BusinessToGoods->name;
                        $BusinessToOrdersGoods->num = $num;
                        $BusinessToOrdersGoods->price = $BusinessToGoods->price;
                        $BusinessToOrdersGoods->total_price = bcmul($BusinessToGoods->price, $BusinessToOrdersGoods->num);
                        $BusinessToOrdersGoods->order_number = $orderNumber;
                        $BusinessToOrdersGoods->create_time = date('Y-m-d H:i:s');
                        if (!$BusinessToOrdersGoods->save()) {
                            throw new \Exception("Goods Name：{$BusinessToGoods->name}，save order_goods fail");
                        }
                    }
                }
                //订单表创建数据
                $User = $this->_user->getInfoByOpenid(Session::get('wechat_user.id'));
                $BusinessToOrder = new BusinessToOrders();
                $BusinessToOrder->bid = $params['bid'];
                $BusinessToOrder->order_number = $orderNumber;
                $BusinessToOrder->uid = $User->uid;
                $BusinessToOrder->total_price = $this->caculateTotalPrice($params['goods']);
                $BusinessToOrder->total_price_chy = bcmul($BusinessToOrder->total_price, $Currency->result);
                $BusinessToOrder->exchange_rate = $Currency->result;
                $BusinessToOrder->user_name = $params['name'];
                $BusinessToOrder->user_telephone = $params['telephone'];
                $BusinessToOrder->user_address = $params['address'];
                $BusinessToOrder->user_post_code = $params['post_code'];
                $BusinessToOrder->create_time = date('Y-m-d H:i:s');
                if (!$BusinessToOrder->save()) {
                    throw new \Exception("Save order fail");
                }
                Db::commit();
                return json(['errcode' => 0, 'errmsg' => 'ok', 'orderId' => $orderNumber]);
            } catch (\Exception $e) {
                Db::rollback();
                return json(['errcode' => -3, 'errmsg' => $e->getMessage()]);
            }
        }

    }

    public function getShoppingCartInfo()
    {
        switch ($this->method) {
            case 'get':
            case 'post':
                $json_array = [];
                if ($this->method == 'get') {
                    $json_array = json_decode(input('get.json'), true);
                } else {
                    $json_array = json_decode(input('post.json'), true);
                }
                $final_json = $this->output_json_template;
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
                    $goods_list[$value['gid']] = ['name' => $value['name'], 'num' => $json_array[$value['gid']]];
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