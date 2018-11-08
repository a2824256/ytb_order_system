<?php
/**
 * Created by PhpStorm.
 * User: AlexLeung
 * Date: 2018/11/5
 * Time: 7:15
 */

namespace app\business\controller;

use \think\Controller;
use \app\business\model\BusinessToGoods;
use \app\business\model\BusinessToGoodsClassifications;
use \app\common\model\BusinessAccount;
use \think\Session;

class Form extends Controller
{
    public function deleteGood()
    {
        $account = Session::get('business');
        if (!empty($account)) {
            $gid = input('get.gid');
            $res = BusinessToGoods::where(['gid' => $gid])->delete();
            if ($res) {
                $this->success('Delete success.', 'index/goods');
            } else {
                $this->error('Delete fail.');
            }
        } else {
            $this->error('Please login!', 'index/index');
        }
    }

    public function deleteClassifications()
    {
        $account = Session::get('business');
        if (!empty($account)) {
            $cid = input('get.cid');
            $res = BusinessToGoodsClassifications::where(['cid' => $cid])->delete();
            if ($res) {
                $this->success('Delete success.', 'index/classifications');
            } else {
                $this->error('Delete fail.');
            }
        } else {
            $this->error('Please login!', 'index/index');
        }
    }

    public function logout()
    {
        Session::clear();
        $this->success('log out.', 'index/index');
    }

    public function printOrder(){
        $oid = input('get.oid');
        $order = BusinessToOrders::where(['order_number' => $oid])->field('user_name,user_post_code,user_telephone,user_address,bid,comment,total_price,order_number,create_time')->find();
        $goods = BusinessToOrdersGoods::where(['order_number' => $oid])->column('gid,good_name,num,price,total_price');
        $business = BusinessAccount::where(['bid' => $order['bid']])->field('name,phone,device_id')->find();
        $string = "<1B40><1B40><1B40><1D2111><1B6101>".$business['name']."<0D0A><1B6100><1D2100><0D0A>顾客昵称： ".$order['user_name']." <0D0A>联系电话： ".$order['user_telephone']."<0D0A>邮编： ".$order['user_post_code']."<0D0A>配送地址： ".$order['user_address']."<0D0A>订单详情: ";
        foreach ($goods as $key => $value){
            $string .= "<0D0A>".$value['good_name']."  x".$value['num']."  ￡".$value['total_price'];
        }
        $string .= "<0D0A>总计： ￡".$order['total_price']."<0D0A>备注: ".$order['comment']."<0D0A>订单号: ytb".$order['order_number']."<0D0A>下单时间: ".$order['create_time']."<0D0A><0D0A><0D0A><0D0A><0D0A><0D0A><0D0A><0D0A>";
        $param = "dingdanID="."000099996123".$order['order_number']."&dayinjisn=".$business['device_id']."&pages=1&dingdan=";
        $ch = curl_init();
        $url = "115.28.15.113:61111/?".$param.rawurlencode($string);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $output = curl_exec($ch);
        curl_close($ch);
        if($output == "OK"){
            $this->success('Print success.', 'index/index');
        }else{
            $this->error($output);
        }

    }

}