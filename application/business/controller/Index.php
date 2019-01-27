<?php

namespace app\business\controller;

use \think\View;
use \think\Session;
use \app\common\model\BusinessAccount;
use \app\business\model\BusinessToGoods;
use \app\business\model\BusinessToOrders;
use \app\business\model\BusinessToGoodsClassifications;
use \think\Controller;

class Index extends Controller
{
    protected $beforeActionList = [
        'checkSession' => ['except' => 'index'],

    ];

    public function index()
    {
        $view = new View();
        return $view->fetch();
    }

    public function checkSession()
    {
        if (Session::get('business') == null) {
            $this->error('Please login~');
        }
    }

    public function main()
    {
        $orders = BusinessToOrders::where(['create_time'=>date("Y-m-d H:i:s")."%"])->count();
        $rec = BusinessToOrders::where(['create_time'=>date("Y-m-d H:i:s")."%"])
            ->where("status" ,'in' ,'1,2')->sum('total_price');
        $view = new View();
        $view->orders = $orders;
        $view->rec = $rec;
        return $view->fetch();
    }

    public function goods()
    {
        $bid = Session::get('bid');
        $key = input('post.key');
        $orders = null;
        if (!empty($key)) {
            $goods = BusinessToGoods::where('name', 'like', '%' . $key . '%')
                ->whereOr('price', 'like', '%' . $key . '%')
                ->field('gid,name,price,info,cid,pic')
                ->paginate(10);
        } else {
            $goods = BusinessToGoods::where(['bid' => $bid])->field('gid,name,price,info,cid,pic')->paginate(10);
        }
        $class = BusinessToGoodsClassifications::where(['bid' => $bid])->column('cid,name');
        foreach ($goods as $key => $value) {
            $goods[$key]['cid'] = $class[$value['cid']];
        }
        $view = new View();
        $view->goods = $goods;
        $view->class = $class;
        return $view->fetch();
    }

    public function classifications()
    {
        $bid = Session::get('bid');
        $key = input('post.key');
        $class = null;
        if (!empty($key)) {
            $class = BusinessToGoodsClassifications::where('name', 'like', '%' . $key . '%')->field('cid,name')->paginate(10);
        } else {
            $class = BusinessToGoodsClassifications::where(['bid' => $bid])->field('cid,name')->paginate(10);
        }
        $view = new View();
        $view->class = $class;
        return $view->fetch();
    }

    public function orders()
    {
        $bid = Session::get('bid');
        $key = input('post.key');
        $orders = null;
        if (!empty($key)) {
            $orders = BusinessToOrders::where('status','in','1,2')
                ->whereOr('order_number', 'like', '%' . $key . '%')
                ->whereOr('total_price', 'like', '%' . $key . '%')
                ->whereOr('user_name', 'like', '%' . $key . '%')
                ->whereOr('user_post_code', 'like', '%' . $key . '%')
                ->whereOr('user_telephone', 'like', '%' . $key . '%')
                ->whereOr('user_address', 'like', '%' . $key . '%')
                ->field('step,oid,order_number,total_price,status,user_name,user_telephone,create_time')
                ->order('oid desc')
                ->paginate(10);
        } else {
            $orders = BusinessToOrders::where(['bid' => $bid])->where('status','in','1,2')->order('oid desc')->field('step,oid,order_number,total_price,status,user_name,user_telephone,create_time')->paginate(10);
        }
        foreach($orders as &$val){
            $val['total_price'] = $val['total_price'] / 100;
        }
        $view = new View();
        $view->orders = $orders;
        return $view->fetch();
    }

    public function autoGetData(){
        $oid = input('get.oid');
        $result = [];
        $newData = BusinessToOrders::where('oid','>',$oid)->join('business_to_orders_goods','business_to_orders_goods.order_number = business_to_orders.order_number')->where(['step' => 0])->where('status','in','1,2')->field('business_to_orders.*,business_to_orders_goods.good_name,business_to_orders_goods.num,business_to_orders_goods.price,business_to_orders_goods.total_price as goods_total_price')->select();
        foreach($newData as $val){
            if(array_key_exists($val['order_number'],$result)){
                $data = [
                    'good_name' => $val['good_name'],
                    'num' => $val['num'],
                    'price' => $val['price'],
                    'goods_total_price' => $val['goods_total_price']
                ];
                $result[$val['order_number']]['goods'][] = $data;
            }else{
                $result[$val['order_number']]['oid'] = $val['oid'];
                $result[$val['order_number']]['bid'] = $val['bid'];
                $result[$val['order_number']]['order_number'] = $val['order_number'];
                $result[$val['order_number']]['uid'] = $val['uid'];
                $result[$val['order_number']]['total_price'] = $val['total_price'];
                $result[$val['order_number']]['total_price_cny'] = $val['total_price_cny'];
                $result[$val['order_number']]['status'] = $val['status'];
                $result[$val['order_number']]['step'] = $val['step'];
                $result[$val['order_number']]['user_name'] = $val['user_name'];
                $result[$val['order_number']]['user_post_code'] = $val['user_post_code'];
                $result[$val['order_number']]['user_telephone'] = $val['user_telephone'];
                $result[$val['order_number']]['user_address'] = $val['user_address'];
                $result[$val['order_number']]['comment'] = $val['comment'];
                $result[$val['order_number']]['create_time'] = $val['create_time'];
                $result[$val['order_number']]['goods'][] = [
                    'good_name' => $val['good_name'],
                    'num' => $val['num'],
                    'price' => $val['price'],
                    'goods_total_price' => $val['goods_total_price']
                ];
            }
        }
        if(!empty($result)){
            return json(['errcode' => 0,'errmsg' => 'New Data','data' => array_values($result)]);
        }else{
            return json(['errcode' => -1,'errmsg' => 'Not Data']);
        }
    }

    public function business()
    {
        $bid = Session::get('bid');
        $info = BusinessAccount::where(['bid' => $bid])->field('name,pic,phone,device_id,start_hour,start_min,end_hour,end_min,cpc,dp,bg')->find();
        $view = new View();
        $view->info = $info;
        return $view->fetch();
    }
}
