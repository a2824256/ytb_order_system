<?php

namespace app\express\controller;

use \think\View;
use \think\Session;
use \app\business\model\BusinessToOrders;
use \think\Controller;
use \app\common\model\BusinessAccount;
use think\Db;

class Index extends Controller
{
    public function index()
    {
        $acc = Session::get('acc');
        if ($acc != null) {
            $this->success("你已登录", 'express/index/main', 200, 1);
        }
        $view = new View();
        return $view->fetch();
    }

    public function main()
    {
        $acc = Session::get('acc');
        if (empty($acc)) {
            $this->error("请登录！");
        }
        $list = Db::table("business_to_orders")->alias('bo')->join('business_account ba','bo.bid = ba.bid')
            ->where("bo.status" ,'in' ,'1,2')
            ->field('bo.oid,bo.order_number,bo.user_name,bo.user_post_code,bo.user_telephone,bo.user_address,bo.create_time,bo.step,ba.name as bussiness_name')
            ->order('bo.oid', 'desc')
            ->paginate(5);
//        foreach ($list as $key => $value) {
//            $list[$key]['user_post_code'] = strtoupper($value['user_post_code']);
//            $list[$key]['user_telephone'] = preg_replace('# #', '', $value['user_telephone']);
//        }
        $view = new View();
        $view->list = $list;
        return $view->fetch();
    }

}
