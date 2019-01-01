<?php
/**
 * Created by PhpStorm.
 * User: AlexLeung
 * Date: 2018/11/7
 * Time: 7:51
 */

namespace app\wechat\controller;

use app\wechat\model\BusinessCurrency;
use app\wechat\model\BusinessToGoodsAttributes;
use app\wechat\model\BusinessToOrders;
use app\wechat\model\BusinessToOrdersGoods;
use app\wechat\model\OrderDeliveryman;
use app\wechat\model\OrderNumber;
use \think\controller\Rest;
use think\Db;
use \think\Response;
use \app\common\model\BusinessAccount;
use \app\wechat\model\User;
use \app\business\model\BusinessToGoods;
use \app\business\model\BusinessToGoodsClassifications;

class Api extends Rest
{
//    private $header = ["Access-Control-Allow-Method" => "*", "Access-Control-Allow-Origin" => "http://business.szfengyuecheng.com", "Access-Control-Allow-Credentials" => true, "Access-Control-Allow-Headers" => "Origin, X-Requested-With, Content-Type, Accept"];
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
                $json['data'] = $bussinessList->field('name,phone,start_hour,start_min,end_hour,end_min,pic,cpc,bid,dp')->where(['status' => 0])->order("weight DESC")->select();
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

    /**
     * 分类及商品展示
     */
    public function goodsAndClassList()
    {
        switch ($this->method) {
            case 'get':
                $bid = input('get.bid');
                $bussinessList = new BusinessAccount();
                $json['business_info'] = $bussinessList->where(['bid' => input('get.bid')])->field('name,phone,address,start_hour,start_min,end_hour,end_min,pic,cpc,bid,dp,recommend')->find();
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
                $attribute = new BusinessToGoodsAttributes();
                $goods_list = $goods->where(['bid' => $bid])->field('name,price,pic,cid,gid,is_recommend')->select();
                $classes = $class->where(['bid' => $bid])->field('name,cid')->order('weight desc')->select();
                $json['goods'] = [];
                $json['class_title'] = [];
                $recommend = [];
                //热门
                $hot = $goods->where(['bid' => $bid])->field('name,price,pic,cid,gid,is_recommend')->order('sell_quantity desc,create_time desc')->limit(5)->select();
                //热门商品属性赋值
                foreach($hot as &$h){
                    $h['attribute'] = $attribute->where(['gid' => $h['gid'],'deleted' => 0])->select();
                }
                $json['goods']['0'] = $hot;
                foreach($classes as $k=>$value){
                    $json['class_title'][$k]['title'] = $value['name'];
                    $json['class_title'][$k]['id'] = $value['cid'];
                }
                array_unshift($json['class_title'],['title' => '热门商品','id' => 0]);
                foreach ($goods_list as $key => $value2) {
                    $value2['attribute'] = $attribute->where(['gid' => $value2['gid'],'deleted' => 0])->select();
                    $json['goods'][$value2['cid']][] = $value2;
                    //商家推荐
                    if($value2['is_recommend'] === 1){
                        $recommend[] = $value2;
                    }
                }
                //是否允许商家推荐
                if($json['business_info']['recommend'] === 1){
                    $json['business_info']['recommend'] = $recommend;
                }else{
                    $json['business_info']['recommend'] = [];
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
     * goods => ['good_id' => ['attribute_id' => $attribute_id,'number' => $number]];
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
            'comment' => trim(input(['post.comment']))
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
                $BusinessToOrder->comment = $params['comment'];
                $BusinessToOrder->json = json_encode($params['json']);
                if (!$BusinessToOrder->save()) {
                    throw new \Exception("订单创建失败1");
                }
                //订单记录表
                foreach ($params['goods'] as $gid => $attribute) {
                    $BusinessToGoods = BusinessToGoods::get($gid);
                    if ($BusinessToGoods) {
                        $attributeObj = BusinessToGoodsAttributes::get($attribute['attribute_id']);
                        if(empty($attributeObj)){
                            //商品没有属性的情况
                            $price = $BusinessToGoods->price;
                        }else{
                            $price = $attributeObj->price;
                        }
                        $BusinessToOrdersGoods = new BusinessToOrdersGoods();
                        $BusinessToOrdersGoods->good_name = $BusinessToGoods->name;
                        $BusinessToOrdersGoods->num = $attribute['number'];
                        $BusinessToOrdersGoods->price = $price;
                        $BusinessToOrdersGoods->total_price = bcmul($price, $attribute['number']);
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

    /**
     * 用户删除订单
     */
    public function deleteOrder(){
        switch ($this->method) {
            case 'post':
                $param = [
                    'uid' => trim(input('post.uid')),
                    'order_number' => trim(input('post.order_number'))
                ];
                $order = (new BusinessToOrders())->where(['uid' => $param['uid'],'order_number' => $param['order_number'],'deleted' => 0])->find();
                if(empty($order)){
                    return Response::create(['errcode' => -1,'errmsg' => '找不到该订单'], 'json', 200, $this->header);
                }
                if((new BusinessToOrders())->where(['uid' => $param['uid'],'order_number' => $param['order_number'],'deleted' => 0])->setField('deleted',1)){
                    return Response::create(['errcode' => 0,'errmsg' => '操作成功'], 'json', 200, $this->header);
                }else{
                    return Response::create(['errcode' => -1,'errmsg' => '操作失败'], 'json', 200, $this->header);
                }
        }
    }


    /**
     * 历史订单
     */
    public function allOrders()
    {
        switch ($this->method) {
            case 'get':
                $uid = input('get.uid');
                $final_json['status'] = 1;
                $order_goods = [];
                $orders = BusinessToOrders::where(['uid'=>$uid,'status'=>1])
                    ->join('business_to_orders_goods','business_to_orders_goods.order_number = business_to_orders.order_number')
                    ->field('business_to_orders.order_number,business_to_orders.total_price as order_total_price,business_to_orders.create_time,business_to_orders_goods.good_name,business_to_orders_goods.num,format(business_to_orders_goods.price,2) as price,business_to_orders_goods.total_price as good_total_price')
                    ->select()
                    ->toArray();
                foreach($orders as $key =>$value){
                    if(array_key_exists($value['order_number'],$order_goods)){
                        //存在多条账单时处理逻辑
                        $data = [
                            'good_name' => $value['good_name'],
                            'num' => $value['num'],
                            'price' => round($value['price'],2),
                            'good_total_price' => round($value['good_total_price'],2),
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
                                    'price' => round($value['price'],2),
                                    'good_total_price' => round($value['good_total_price'],2),
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

    /**
     * 商家信息
     */
    private function getBusiness($bid = ''){
        $businessObj = BusinessAccount::get($bid);
        $business = [
            'name' => $businessObj->name,
            'bid' => $businessObj->bid,
            'phone' =>  $businessObj->phone
        ];
        return $business;
    }

    /**
     * 商品信息
     */
    private function getGoods($goods = []){
        $total = 0;
        $data = [];
        $result = [];
        foreach($goods as $gid => $value){
            $good = BusinessToGoods::get($gid);
            $data[$gid]['good_name'] = $good->name;
            $data[$gid]['gid'] = $good->gid;
            $data[$gid]['pic'] = $good->pic;
            $data[$gid]['price'] = $good->price;

            //属性信息
            $attribute = BusinessToGoodsAttributes::get($value['attribute_id']);
            if(!empty($attribute)){
                $arr['id'] = $attribute->id;
                $arr['title'] = $attribute->title;
                $arr['price'] = $attribute->price;
                $arr['number'] = $attribute->quantity;
                $total_gbp = bcadd($attribute->price,$total,2);
            }else{
                //价格
                $total_gbp = bcadd($good->price,$total,2);
            }
            $result[$gid]['attribute'] = !empty($attribute) ? $arr : [];
        }
        //商品
        $result['goods'] = $data;
        //总价
        //英镑
        $result['total']['gbp'] = $total_gbp;
        //固定兑换人民币比例
        $result['total']['cny'] = bcmul($total_gbp, 9,2);
        return $result;
    }

    /**
     * 骑手信息
     */
    private function getDeliveryman($order_number = ''){
        return  (new OrderDeliveryman())->where(['order_number' => $order_number])->join('deliveryman','deliveryman.did = order_deliveryman.did')->field('did,telephone,name')->find();
    }

    /**
     * 订单详情接口
     * goods => ['good_id' => ['attribute_id' => $attribute_id,'number' => $number]];
     */
    public function getOrder()
    {
        switch ($this->method) {
            case 'get':
                $result = [];
                $param = [
                    'bid' => trim(input('get.bid')),
                    'goods' => json_decode(input('get.json'), true)
                ];
                //商家信息
                $result['business'] = $this->getBusiness($param['bid']);
                //物品信息
                $goods = $this->getGoods($param['goods']);
                $result['total'] = $goods['total'];
                $result['goods'] = $goods['goods'];
                return Response::create($result, 'json', 200, $this->header);
            case 'post':
        }
    }

    /**
     * 订单完成接口
     */
    public function completeOrder(){
        switch ($this->method) {
            case 'get':
                $result = [];
                $param = [
                    'order_number' => trim(input('get.order_number'))
                ];
                $order = (new BusinessToOrders())->where(['order_number' => $param['order_number']])->find();
                //商家信息
                $result['business'] = $this->getBusiness($order->bid);
                //物品信息
                $goods = $this->getGoods($order->json);
                $result['total'] = $goods['total'];
                $result['goods'] = $goods['goods'];
                //骑手信息
                $result['deliveryman'] = $this->getDeliveryman($order->order_number);
                //配送信息
                $result['distribution'] = [
                    'user_name' => $order->user_name,
                    'user_post_code' => $order->user_post_code,
                    'user_telephone' => $order->user_telephone,
                    'user_address' => $order->user_address,
                ];
                //订单信息
                $result['order'] = [
                    'order_number' => $order->order_number,
                    'create_time'  => $order->create_time
                ];
                return Response::create($result, 'json', 200, $this->header);
            case 'post':
                break;
        }
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