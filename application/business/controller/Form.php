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
use \app\business\model\BusinessToOrders;
use \app\business\model\BusinessToOrdersGoods;
use \app\business\model\BusinessToGoodsClassifications;
use \app\common\model\BusinessAccount;
use EasyWeChat\Factory;
use \think\Session;
use think\Db;

class Form extends Controller
{
    private $url = "115.28.15.113:61111/?";

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

    public function printOrder()
    {
        $oid = input('get.oid');
        $order = BusinessToOrders::where(['order_number' => $oid])->field('user_name,user_post_code,user_telephone,user_address,bid,comment,total_price,order_number,create_time')->find();
        $goods = BusinessToOrdersGoods::where(['order_number' => $oid])->column('gid,good_name,num,price,total_price');
        $business = BusinessAccount::where(['bid' => $order['bid']])->field('name,phone,device_id')->find();
        $string = "<1B40><1B40><1B40><1D2111><1B6101>" . $business['name'] . "<0D0A><1B6100><1D2100><0D0A>顾客昵称： " . $order['user_name'] . " <0D0A>联系电话： " . $order['user_telephone'] . "<0D0A>邮编： " . $order['user_post_code'] . "<0D0A>配送地址： " . $order['user_address'] . "<0D0A>订单详情: ";
        foreach ($goods as $key => $value) {
            $string .= "<1D2111>" . $value['good_name'] . "  x" . $value['num'] . "  ￡" . $value['total_price'];
        }
        $string .= "<0D0A>配送费： ￡2";
        $string .= "<0D0A>总计： ￡" . $order['total_price'] / 100 . "<0D0A>备注: " . $order['comment'] . "<0D0A>订单号: ytb" . $order['order_number'] . "<0D0A>下单时间: " . $order['create_time'] . "<0D0A><0D0A><0D0A><0D0A><0D0A><0D0A><0D0A><0D0A>";
        $device_arr = explode(',', $business['device_id']);
        $ch = curl_init();
        foreach ($device_arr as $value2) {
            $param = "dingdanID=" . $order['order_number'] . "&dayinjisn=" . $value2 . "&pages=1&dingdan=";
            $url = $this->url . $param . rawurlencode($string);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $output = curl_exec($ch);
            if ($output != "OK") {
                $this->error($output);
            }
        }
        curl_close($ch);
        $this->success('Print success.');
    }


    /**
     * 商户接单/取消
     */
    public function operating()
    {
        $oid = intval(input('get.oid'));
        $op = intval(input('get.op'));
        if (BusinessToOrders::where(['order_number' => $oid])->update(['step' => $op])) {
            $this->success('Successful operation.');
        } else {
            $this->error('Operation failed.');
        }
    }

    private static function send2Deliveryman($orderNumber)
    {
        $config = [
            'app_id' => 'wx007296fc9a7d315f',
            'secret' => '50ddb0815ab75971d407f4218222675c',
            'response_type' => 'array',
        ];
        $app = Factory::officialAccount($config);
        $order_info = BusinessToOrders::where(['order_number' => $orderNumber])->find();
        $business = BusinessAccount::where(['bid' => $order_info['bid']])->find();
        $price = $order_info['total_price'] / 100;
        $total_price_cny = $order_info['total_price_cny'] / 100;
        $time = date('Y-m-d H:i:s', strtotime($order_info['create_time'])+ (20 * 60));
        $dman = Db::table('deliveryman')->field('openid')->select();
        foreach ($dman as $key) {
            $temp = [
                'touser' => $key['openid'],
                'template_id' => 'Avn8YCqx4SGAyjdq5gcPmDFpMsx6iNQOtvGm1OOQocE',
                'data' => [
                    'first' => '新订单通知！！！！！！！',
                    'keyword1' => $orderNumber,
                    'keyword2' => "姓名:".$order_info['user_name'] . '  电话:' . $order_info['user_telephone'],
                    'keyword3' => "地址:".$order_info['user_address'] . '  邮编:' . $order_info['user_post_code'],
                    'keyword4' => '微信支付：' . $total_price_cny . '元（折合' . $price . '磅）',
                    'keyword5' => $time,
                    'remark' => '去'.$business['name'].'取餐，行快两步啦柒头！！',
                ],
                "url" => "http://www.szfengyuecheng.com/express/index/index",
            ];
            $app->template_message->send($temp);
        }
    }
}