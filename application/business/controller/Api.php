<?php

namespace app\business\controller;

use \think\controller\Rest;
use \app\common\model\BusinessAccount;
use \app\business\model\BusinessToOrdersGoods;
use \app\business\model\BusinessToGoods;
use \app\business\model\BusinessToGoodsClassifications;
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

    public function ordersGoods()
    {
        switch ($this->method) {
            case 'post':
//                    $business = openssl_decrypt(base64_decode(input('get.bid')), "aes-128-cbc", config('cbc_key'), OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, config('cbc_iv'));
                $order = input('post.order');
                $goods = BusinessToOrdersGoods::where(['order_number' => $order])->column('gid,good_name,num,price,total_price');
                $output_json = $this->output_json_template;
                $output_json['status'] = 1;
                $output_json['goods'] = $goods;
                return $this->response($output_json, 'json', 200);
        }
    }

    public function goods()
    {
        $output_json = $this->output_json_template;
        switch ($this->method) {
            case 'post':
                if (!is_numeric(input('post.price'))) {
                    $output_json['status'] = 3;
                    return $this->response($output_json, 'json', 200);
                }
                if (!input('?post.name')) {
                    $output_json['status'] = 4;
                    return $this->response($output_json, 'json', 200);
                }
                if (!input('?post.price')) {
                    $output_json['status'] = 4;
                    return $this->response($output_json, 'json', 200);
                }
                $good = new BusinessToGoods;
                $good->name = input('?post.name') ? input('post.name') : '';
                $good->price = input('?post.price') ? input('post.price') : 0;
                $good->info = input('?post.info') ? input('post.info') : '';
                $good->cid = input('?post.cid') ? input('post.cid') : 0;
                $good->pic = input('?post.photo') ? input('post.photo') : '';
                $good->bid = input('?post.bid') ? input('post.bid') : 0;
                $good->create_time = date('Y-m-d H:i:s', time());
                if ($good->save()) {
                    $output_json['status'] = 1;
                }
                return $this->response($output_json, 'json', 200);
        }
    }

    public function classifications()
    {
        $output_json = $this->output_json_template;
        switch ($this->method) {
            case 'post':
                $class = new BusinessToGoodsClassifications;
                $class->name = input('post.name');
                $class->bid = input('post.bid');
                $class->create_time = date('Y-m-d H:i:s', time());
                if ($class->save()) {
                    $output_json['status'] = 1;
                }
                return $this->response($output_json, 'json', 200);
        }
    }

    public function updateInfo()
    {
        $output_json = $this->output_json_template;
        switch ($this->method) {
            case 'post':
                $content = [];
                if (input('?post.photo')) {
                    $content = [
                        'pic' => input('post.photo'),
                        'name' => input('post.name'),
                        'phone' => input('post.phone'),
                        'device_id' => input('post.device_id'),
                    ];
                } else {
                    $content = [
                        'name' => input('post.name'),
                        'phone' => input('post.phone'),
                        'device_id' => input('post.device_id'),
                    ];
                }
                $business = new BusinessAccount();
                $res = $business->where('bid', input('post.bid'))->update($content);
                if ($res) {
                    Session::set('pic', input('post.photo'));
                    $output_json['status'] = 1;
                    return $this->response($output_json, 'json', 200);
                } else {
                    $output_json['res'] = input('post.bid');
                    return $this->response($output_json, 'json', 200);
                }
        }
    }
}
