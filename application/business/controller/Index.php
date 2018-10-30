<?php

namespace app\business\controller;

use \think\View;
use \think\Session;
use \app\common\model\BusinessAccount;
use \app\business\model\BusinessToOrders;
use think\Controller;

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

    }

    public function orders()
    {
        $bid = Session::get('bid');
        $orders = BusinessToOrders::where(['bid' => $bid])->field('oid,order_number,total_price,status,user_name,user_telephone,create_time')->paginate(1);;
        $view = new View();
        $view->orders = $orders;
        return $view->fetch();
    }
}
