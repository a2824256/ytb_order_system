<?php
/**
 * Created by PhpStorm.
 * User: yangxixuan
 * Date: 2019/1/1
 * Time: 16:13
 */

namespace app\mini\controller;

use app\mini\controller\Api;
use think\Db;
use \think\Response;

class Order extends Api
{

    /**
     * 限制每页显示的数据数量
     * @var int
     */
    private $limit = 10;

    /**
     * 订单流程参数枚举
     */
    private $_step = [
        0 => '等待商家接单',
        1 => '商家已确认',
        2 => '商家取消订单',
        3 => '骑手已取餐',
        4 => '骑手已送达',
        5 => '订单已取消',
        6 => '已评价',
    ];

    /**
     * 用户删除订单
     */
    public function deleteOrder()
    {
        switch ($this->method) {
            case 'post':
                $param = [
                    'order_number' => trim(input('post.order_number'))
                ];
                $user = $this->getUser();
                if(empty($user)){
                    return Response::create(['code' => 401,'status' => 0, 'msg' => '用户不存在'], 'json', 200);
                }
                $order = Db::table('order')->where(['uid' => $user['uid'], 'order_number' => $param['order_number'], 'deleted' => 0])->find();
                if (empty($order)) {
                    return Response::create(['code' => 405,'status' => 0, 'msg' => '找不到该订单'], 'json', 200);
                }
                if (Db::table('order')->where(['uid' => $user['uid'], 'order_number' => $param['order_number'], 'deleted' => 0])->setField('deleted', 1)) {
                    return Response::create(['code' => 200, 'status' => 1, 'msg' => '操作成功'], 'json', 200);
                } else {
                    return Response::create(['code' => 405,'status' => 0, 'msg' => '操作失败'], 'json', 200);
                }
        }
    }

    /**
     * 获取订单支付状态
     */
    public function getOrderStatus(){
        switch ($this->method) {
            case 'get':
                $param = [
                    'order_number' => trim(input('get.order_number'))
                ];
                $order = Db::table('order')->where(['order_number' => $param['order_number'], 'deleted' => 0])->find();
                if (empty($order)) {
                    return Response::create(['code' => 405,'status' => 0, 'msg' => '找不到该订单'], 'json', 200);
                }
                return Response::create(['code' => 200,'status' => 1, 'msg' => 'ok','data' => ['order_number' => $param['order_number'],'status' => $order['status']]], 'json', 200);
        }
    }

    /**
     * 订单编号
     */
    private function getOrderNumber()
    {
        $rule = '2018';
        $orderNumber = Db::table('order_number')->where(['id' => 1])->find();
        if (strlen($orderNumber['number']) < 6) {
            $number = $rule . str_pad((string)$orderNumber['number'], 6, '0', STR_PAD_LEFT);
        } else {
            $number = $rule . $orderNumber['number'];
        }
        Db::table('order_number')->where(['id' => 1])->setInc("number");
        return $number;
    }

    /**
     * 结算接口
     * goods => [0 => ['gid' => 222,'attribute_id' => 333,'number' => 1]];
     */
    public function settlement()
    {
        switch ($this->method){
            case 'post':
                $params = [
                    'bid' => trim(input('post.bid')),
                    'goods' => input('post.goods/a'),
                    'name' => trim(input('post.name')),
                    'telephone' => trim(input('post.telephone')),
                    'address' => trim(input('post.address')),
                    'post_code' => trim(input('post.post_code')),
                    'comment' => trim(input('post.comment'))
                ];

                if(empty($params['bid']) || empty($params['goods']) || empty($params['name']) || empty($params['telephone']) || empty($params['address']) || empty($params['post_code'])){
                    return Response::create(['code' => 402,'status' => 0, 'msg' => '参数不正确'], 'json', 200);
                }

                $user = $this->getUser();
                if(empty($user)){
                    return Response::create(['code' => 401,'status' => 0, 'msg' => '用户不存在'], 'json', 200);
                }

                if (empty($params['goods']) || !is_array($params['goods'])) {
                    return Response::create(['code' => 402, 'status' => 0, 'msg' => '参数不正确'], 'json', 200);
                }

                Db::startTrans();
                try {
                    //英镑汇率
                    $currency = Db::table('business_currency')->where(['currencyf' => 'GBP','currencyt' => 'CNY'])->find();
                    //订单编号
                    $orderNumber = $this->getOrderNumber();
                    //订单表创建数据
//                        $User = $this->_user->getInfoByUid();
                    $data_order['bid'] = $params['bid'];
                    $data_order['order_number'] = (int)$orderNumber;
                    $data_order['uid'] = $user['uid'];
                    $data_order['total_price'] = $this->caculateTotalPrice($params['goods']) * 100 + 200;
//                                $data_order['total_price_cny'] = bcmul($Currency->result, (string)$BusinessToOrder->total_price);
                    $data_order['total_price_cny'] = bcmul(9, $data_order['total_price']);
                    $data_order['exchange_rate'] = $currency['result'];
                    $data_order['user_name'] = $params['name'];
                    $data_order['user_telephone'] = $params['telephone'];
                    $data_order['user_address'] = $params['address'];
                    $data_order['user_post_code'] = $params['post_code'];
                    $data_order['create_time'] = date('Y-m-d H:i:s');
                    $data_order['comment'] = $params['comment'];
                    $data_order['json'] = json_encode($params['goods']);
                    if(!Db::table('order')->insert($data_order)){
                        throw new \Exception('订单生成失败');
                    }

                    //订单记录表
                    foreach ($params['goods'] as $key => $good) {
                        $businessToGoods = Db::table('business_to_goods')->where(['gid' => $good['gid']])->find();
                        if ($businessToGoods) {
                            $attributeObj = Db::table('business_to_goods_attributes')->where(['id' => $good['attribute_id']])->find();
                            if (empty($attributeObj)) {
                                //商品没有属性的情况
                                $price = $businessToGoods['price'];
                            } else {
                                $price = $attributeObj['price'];
                            }
                            $data_order_good['good_id'] = $good['gid'];
                            $data_order_good['attribute_id'] = $good['attribute_id'];
                            $data_order_good['good_name'] = $businessToGoods['name'];
                            $data_order_good['num'] = $good['number'];
                            $data_order_good['price'] = $price;
                            $data_order_good['total_price'] = bcmul($price, $good['number'], 2);
                            $data_order_good['order_number'] = (int)$orderNumber;
                            $data_order_good['create_time'] = date('Y-m-d H:i:s');
                            if (!Db::table('order_goods')->insert($data_order_good)) {
                                throw new \Exception('订单记录生成失败');
                            }
                        }
                    }
                    Db::commit();
                    return Response::create(['code' => 200,'status' => 1,'msg' => 'ok', 'data' => ['orderId' =>$orderNumber]], 'json', 200);
                } catch (\Exception $e) {
                    Db::rollback();
                    return Response::create(['code' => 405,'status' => 0,'msg' => $e->getMessage()], 'json', 200);
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
                $page = input('get.page') ? input('get.page') : 1;

                $user = $this->getUser();
                if(empty($user)){
                    return Response::create(['code' => 401,'status' => 0, 'msg' => '用户不存在'], 'json', 200);
                }
                $order_goods = [];
                $orders = Db::table('order')->where(['order.uid' => $user['uid'], 'order.status' => 1,'order.deleted' => 0])
                    ->join('order_goods', 'order_goods.order_number = order.order_number','left')
                    ->join('business_account', 'business_account.bid = order.bid','left')
                    ->join('business_to_goods','business_to_goods.gid = order_goods.good_id','left')
                    ->field('business_to_goods.gid,business_to_goods.pic,business_account.bid,business_account.name,business_account.pic,order.step,order_goods.good_id,order_goods.attribute_id,order.order_number,order.total_price as order_total_price,order.create_time,order_goods.good_name,order_goods.num,format(order_goods.price,2) as price,order_goods.total_price as good_total_price')
                    ->page($page,$this->limit)->select();
                $total = Db::table('order')->where(['order.uid' => $user['uid'], 'order.status' => 1,'order.deleted' => 0])->count();

                foreach ($orders as $key => $value) {
                    $attribute = Db::table('business_to_goods_attributes')->where(['id' => $value['attribute_id']])->find();
                    if (array_key_exists($value['order_number'], $order_goods)) {
                        //存在多条账单时处理逻辑
                        $data = [
                            'gid' => $value['gid'],
                            'pic' => $value['pic'],
                            'good_name' => $value['good_name'],
                            'num' => $value['num'],
                            'price' => round($value['price'], 2),
                            'good_total_price' => round($value['good_total_price'], 2),
                            'attribute_name' => !empty($attribute) ? $attribute['title'] : '',
                            'attribute_id' => !empty($attribute) ? $attribute['id'] : 0,
                        ];
                        $order_goods[$value['order_number']]['goods'][] = $data;
                    } else {
                        //添加第一条订单数据
                        $data = [
                            'business_bid' => $value['bid'],
                            'business_name' => $value['name'],
                            'business_pic' => $value['pic'],
                            'step' => $value['step'],
                            'order_number' => $value['order_number'],
                            'order_total_price' => $value['order_total_price'] / 100,
                            'create_time' => $value['create_time'],
                            'goods' => [
                                [
                                    'gid' => $value['gid'],
                                    'pic' => $value['pic'],
                                    'good_name' => $value['good_name'],
                                    'num' => $value['num'],
                                    'price' => round($value['price'], 2),
                                    'good_total_price' => round($value['good_total_price'], 2),
                                    'attribute_name' => !empty($attribute) ? $attribute['title'] : '',
                                    'attribute_id' => !empty($attribute) ? $attribute['id'] : 0,
                                ]
                            ]
                        ];
                        $order_goods[$value['order_number']] = $data;
                    }
                }
                return Response::create(['code' => 200,'status' => 1,'msg' => 'ok','data' => ['list' => array_values($order_goods),'total' => $total]], 'json', 200);
        }
    }

    /**
     * 订单详情
     */
    public function orderInfo()
    {
        switch ($this->method) {
            case 'get':
                $user = $this->getUser();
                if(empty($user)){
                    return Response::create(['code' => 401,'status' => 0, 'msg' => '用户不存在'], 'json', 200);
                }
                $result = [];
                $param = [
                    'order_number' => trim(input('get.order_number'))
                ];
                $order = Db::table('order')->where(['order_number' => $param['order_number']])->find();
                //商家信息
                $result['business'] = $this->getBusiness($order['bid']);
                //物品信息
                $goods = $this->getGoods(json_decode($order['json'],true));
                $result['total'] = ['gbp' => $order['total_price'] / 100, 'cny' => $order['total_price_cny'] / 100];
                $result['goods'] = $goods['goods'];
                //骑手信息
                $result['deliveryman'] = $this->getDeliveryman($order['order_number']);
                //配送信息
                $result['distribution'] = [
                    'user_name' => $order['user_name'],
                    'user_post_code' => $order['user_post_code'],
                    'user_telephone' => $order['user_telephone'],
                    'user_address' => $order['user_address'],
                ];
                //订单信息
                $result['order'] = [
                    'step' => $order['step'],
                    'order_number' => $order['order_number'],
                    'create_time' => $order['create_time'],
                ];
                return Response::create(['code' => 200,'status' => 1,'msg' => '操作成功','data' => $result], 'json', 200);
            case 'post':
                break;
        }
    }

    /**
     * 商家信息
     */
    private function getBusiness($bid = '')
    {
        $businessArray = Db::table('business_account')->where(['bid' => $bid])->find();
        $business = [
            'name' => $businessArray['name'],
            'bid' => $businessArray['bid'],
            'phone' => $businessArray['phone']
        ];
        return $business;
    }

    /**
     * 商品信息
     * $goods = [0 => ['gid' => 222,'attribute_id' => 333,'number' => 1]];
     */
    private function getGoods($goods = [])
    {
        $total_gbp = 0;
        $data = [];
        $result = [];
        foreach ($goods as $key => $value) {
            $good = Db::table('business_to_goods')->where(['gid' => $value['gid']])->find();
            $data[$key]['good_name'] = $good['name'];
            $data[$key]['gid'] = $good['gid'];
            $data[$key]['pic'] = $good['pic'];
            $data[$key]['price'] = $good['price'];
            $data[$key]['number'] = $value['number'];
            //属性信息
            $attribute = Db::table('business_to_goods_attributes')->where(['id' => $value['attribute_id']])->find();
            if (!empty($attribute)) {
                $arr['id'] = $attribute['id'];
                $arr['title'] = $attribute['title'];
                $arr['price'] = $attribute['price'];
                $arr['number'] = $value['number'];
                $data[$key]['total'] = bcmul($value['number'],$attribute['price'],2);
            } else {
                $data[$key]['total'] = bcmul($value['number'],$good['price'],2);
            }
            $total_gbp = bcadd($data[$key]['total'], $total_gbp, 2);
            $data[$key]['attribute'] = !empty($attribute) ? $arr : [];
        }
       
        //商品
        $result['goods'] = $data;
        //总价
        //英镑
        $result['total']['gbp'] = $total_gbp;
        //固定兑换人民币比例
        $result['total']['cny'] = bcmul($result['total']['gbp'], 9, 2);
        return $result;
    }

    /**
     * 骑手信息
     */
    private function getDeliveryman($order_number = '')
    {
        return Db::table('order_deliveryman')->where(['order_number' => $order_number])->join('deliveryman', 'deliveryman.did = order_deliveryman.did')->field('deliveryman.did,deliveryman.telephone,deliveryman.name')->find();
    }


    /**
     * 计算总价
     */
    private function caculateTotalPrice($goods = [])
    {
        if(!isset($goods) || empty($goods)){
            return 0;
        }
        $total_price = 0;
        foreach($goods as $k=>$val){
            $goods_info = Db::table('business_to_goods')->where(['gid' => $val['gid']])->find();
            if($val['attribute_id'] != 0){
                //商品选择了规格
                $attribute = Db::table('business_to_goods_attributes')->where(['id' => $val['attribute_id']])->find();
                $price = bcmul($val['number'],$attribute['price'],2);
            }else{
                //商品没有选择规格
                $price = bcmul($val['number'],$goods_info['price'],2);
            }
            $total_price = bcadd($price,$total_price,2);
        }
        return $total_price;
    }

}