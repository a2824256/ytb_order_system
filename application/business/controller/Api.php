<?php

namespace app\business\controller;

use \think\controller\Rest;
use \app\common\model\BusinessAccount;
use \app\business\model\BusinessToOrders;
use \think\Session;

class Api extends Rest
{
    private $output_json_template = [
        'status' => 0
    ];

    public function login()
    {
        switch ($this->method) {
            case 'post':
                $account = input('post.account');
                $password = input('post.password');
                $output_json = $this->output_json_template;
                $business = BusinessAccount::get(['account' => $account, 'password' => $password]);
                if (!empty($business)) {
                    Session::set('business', $account);
                    Session::set('name', $business->name);
                    Session::set('bid', $business->bid);
                    Session::set('pic', $business->pic);
                    $business->isUpdate(true)->save(['bid' => $business->bid, 'login_time' => date('Y-m-d H:i:s', time())]);
                    $output_json = $business->visible(['bid'])->toArray();
                    $output_json['status'] = 1;
//                    $output_json['session_account'] = Session::get('business', 'ytb');
                } else {
                    $output_json['status'] = 2;
                }
                return $this->response($output_json, 'json', 200);
        }
    }

    public function orders()
    {
        switch ($this->method) {
            case 'get':
//                    $business = openssl_decrypt(base64_decode(input('get.bid')), "aes-128-cbc", config('cbc_key'), OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, config('cbc_iv'));
                $bid = input('get.bid');
                $orders = BusinessToOrders::get(['order_number'=>123123123]);
                var_dump($orders);
//                return $this->response($orders->goods(), 'json', 200);
        }
    }
}
