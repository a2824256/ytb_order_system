<?php

namespace app\printer\controller;

use \think\controller\Rest;
use \app\common\model\BusinessAccount;
use \app\business\model\BusinessToOrdersGoods;
use \app\business\model\BusinessToOrders;
use \app\business\model\BusinessToGoodsClassifications;
use \think\Session;

class Api
{

//    private $url = "115.28.15.113:60003/?";
    private $url = "115.28.15.113:61111/?";
    private $output_json_template = [
        'status' => 0
    ];

    public function printOrder($billoid)
    {
        $oid = $billoid;
        $order = BusinessToOrders::where(['order_number' => $oid])->field('user_name,user_post_code,user_telephone,user_address,bid,comment,total_price,order_number,create_time')->find();
        $goods = BusinessToOrdersGoods::where(['order_number' => $oid])->column('gid,good_name,num,price,total_price');
        $business = BusinessAccount::where(['bid' => $order['bid']])->field('name,phone,device_id')->find();
        $string = "<1B40><1B40><1B40><1D2111><1B6101>" . $business['name'] . "<0D0A><1B6100><1D2100><0D0A>顾客昵称： " . $order['user_name'] . " <0D0A>联系电话： " . $order['user_telephone'] . "<0D0A>邮编： " . $order['user_post_code'] . "<0D0A>配送地址： " . $order['user_address'] . "<0D0A>订单详情: ";
        foreach ($goods as $key => $value) {
            $string .= "<0D0A>" . $value['good_name'] . "  x" . $value['num'] . "  ￡" . $value['total_price'];
        }
        $string .= "<0D0A>配送费： ￡2";
        $string .= "<0D0A>总计： ￡" . $order['total_price']  / 100 . "<0D0A>备注: " . $order['comment'] . "<0D0A>订单号: ytb" . $order['order_number'] . "<0D0A>下单时间: " . $order['create_time'] . "<0D0A><0D0A><0D0A><0D0A><0D0A><0D0A><0D0A><0D0A>";
        $device_arr = explode(',', $business['device_id']);
        $ch = curl_init();
        foreach ($device_arr as $value2) {
            $param = "dingdanID=" . $order['order_number'] . "&dayinjisn=" . $value2 . "&pages=1&dingdan=";
            $url = $this->url . $param . rawurlencode($string);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $output = curl_exec($ch);
        }
        curl_close($ch);
        $resj['status'] = $output;
        return $this->response($resj, 'json', 200);
    }
}
