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
        $account = Session::get('business');
        $business = BusinessAccount::get(['account' => $account]);
        if (!empty($business)) {
            $business_info = $business->visible(['account', 'name', 'pic'])->toArray();
            $view = new View();
            $view->business_info = $business_info;
            return $view->fetch();
        } else {
            exit($account);
        }
    }

    public function goods()
    {
        $bid = Session::get('bid');
        $key = input('post.key');
        $orders = null;
        if (!empty($key)){
            $goods = BusinessToGoods::where('name','like','%'.$key.'%')
                ->whereOr('price','like','%'.$key.'%')
                ->field('gid,name,price,info,cid,pic')
                ->paginate(10);
        }else{
            $goods = BusinessToGoods::where(['bid' => $bid])->field('gid,name,price,info,cid,pic')->paginate(10);
        }
        $class = BusinessToGoodsClassifications::where(['bid' => $bid])->column('cid,name');
        foreach ($goods as $key => $value){
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
        if (!empty($key)){
            $class = BusinessToGoodsClassifications::where('name','like','%'.$key.'%')->field('cid,name')->paginate(10);
        }else{
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
        if (!empty($key)){
            $orders = BusinessToOrders::where('order_number','like','%'.$key.'%')
                ->whereOr('total_price','like','%'.$key.'%')
                ->whereOr('user_name','like','%'.$key.'%')
                ->whereOr('user_post_code','like','%'.$key.'%')
                ->whereOr('user_telephone','like','%'.$key.'%')
                ->whereOr('user_address','like','%'.$key.'%')
                ->field('oid,order_number,total_price,status,user_name,user_telephone,create_time')
                ->paginate(10);
        }else{
            $orders = BusinessToOrders::where(['bid' => $bid])->field('oid,order_number,total_price,status,user_name,user_telephone,create_time')->paginate(10);
        }
        $view = new View();
        $view->orders = $orders;
        return $view->fetch();
    }
}
