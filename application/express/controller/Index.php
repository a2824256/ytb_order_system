<?php
namespace app\express\controller;

use \think\View;
use \think\Session;
use \app\business\model\BusinessToOrders;
use \think\Controller;

class Index extends Controller
{
    public function index()
    {
        $view = new View();
        return $view->fetch();
    }

    public function main()
    {
        $acc = Session::get('acc');
        if(empty($acc)){
            $this->error("请登录！");
        }
        $list = BusinessToOrders::where(["status"=>1])->where('step','in','1,3,4,5')
            ->field('oid,order_number,user_name,user_post_code,user_telephone,user_address,create_time,step')
            ->paginate(10);
        $view = new View();
        $view->list = $list;
        return $view->fetch();
    }
}
