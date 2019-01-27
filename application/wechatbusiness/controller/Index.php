<?php

namespace app\wechatbusiness\controller;

use app\wechat\model\User;
use \think\Controller;
use think\Db;
use \think\View;
use \app\common\model\BusinessAccount;
use app\wechat\model\BusinessToOrders;

class Index extends Controller
{
    public function index($orderNumber, $openid)
    {
        if ($orderNumber == null || $orderNumber == '' || $openid == null || $openid == '') {
            echo '无权访问1';
            die();
        }
        $businessAcc = BusinessAccount::where(['manager' => $openid])->find();
        if (!$businessAcc) {
            echo '无权访问2';
            die();
        }

        $info = Db::table('order')->where(['order_number' => $orderNumber])->find();
        $orders = Db::table('order')->where(['bid' => $businessAcc['bid'], 'status' => 1, 'order_goods.order_number' => $orderNumber])
            ->join('order_goods', 'order_goods.order_number = order.order_number')
            ->field('order.order_number,order.total_price as order_total_price,order.create_time,order_goods.good_name,order_goods.num,format(order_goods.price,2) as price,order_goods.total_price as good_total_price')
            ->select()
            ->toArray();
//        var_dump($orders);
        if (!$orders) {
            echo '无权访问3';
            die();
        }
        $view = new View();
        $view->info = $info;
        $view->order = $orders;
        $view->openid = $openid;
        $view->onum = $orderNumber;
        $view->status = $info['status'];
        $view->step = $info['step'];
        return $view->fetch();
    }

    public function operating($oid,$openid,$type)
    {
        if($oid == null||$oid==''||$openid == null||$openid==''||$type == null||$type==''){
            $this->error('异常操作.');
        }

        $res = Db::table('order')->where(['order_number' => $oid])->find();
        $businessAcc = BusinessAccount::where(['manager' => $openid])->find();
        if($res['bid']!=$businessAcc['bid']){
            $this->error('无操作权限.'.$openid);
        }
        if ($res['step'] < 2 && $type == 3) {
            if (Db::table('order')->where(['order_number' => $oid])->update(['step' => 3])) {
                $this->success('操作成功.');
            } else {
                $this->error('订单已被取消或派送员已取餐，无法修改订单状态.');
            }
        }elseif ($res['step'] == 3 && $type == 4){
            if (Db::table('order')->where(['order_number' => $oid])->update(['step' => 4])) {
                $this->success('操作成功.');
            } else {
                $this->error('骑手尚未取餐.');
            }
        }elseif ($res['step'] == 0 && $type == 2){
            if (Db::table('order')->where(['order_number' => $oid])->update(['step' => 2])) {
                $this->success('操作成功.');
            } else {
                $this->error('非未操作状态，无法改变订单状态.');
            }
        }else{
            $this->error('异常操作.');
        }
    }
}