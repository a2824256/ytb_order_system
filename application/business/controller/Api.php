<?php

namespace app\business\controller;

use app\business\model\BusinessToGoodsAttributes;
use \think\controller\Rest;
use \app\common\model\BusinessAccount;
use \app\business\model\BusinessToOrdersGoods;
use \app\business\model\BusinessToGoods;
use \app\business\model\BusinessToGoodsClassifications;
use think\Db;
use think\Exception;
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

    /**
     * 删除商品属性
     */
    public function deleteAttribute(){
        switch ($this->method){
            case 'post':
                $attribute_id = input('post.attribute_id');
                if((new BusinessToGoodsAttributes())->where(['id' => $attribute_id])->setField('deleted',1)){
                    $output_json = 1;
                    return $this->response($output_json, 'json', 200);
                }else{
                    return $this->response(3, 'json', 200);
                }
        }
    }

    /**
     * 添加商品
     * 属性结构 [['title' => $title,'price' => 'price','gid' => $gid]...]
     * @return \think\Response
     */
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
                    $output_json['status'] = 5;
                    return $this->response($output_json, 'json', 200);
                }
                Db::startTrans();
                try{
                    $good = new BusinessToGoods;
                    $good->name = input('?post.name') ? input('post.name') : '';
                    $good->price = input('?post.price') ? input('post.price') : 0;
                    $good->info = input('?post.info') ? input('post.info') : '';
                    $good->cid = input('?post.cid') ? input('post.cid') : 0;
                    $good->pic = input('?post.photo') ? input('post.photo') : '';
                    $good->bid = input('?post.bid') ? input('post.bid') : 0;
                    $good->create_time = date('Y-m-d H:i:s', time());
                    if (!$good->save()) {
                        throw new Exception(6);
                    }
                    $gid = $good->getLastInsID();
                    $attributes = input('?post.attribute') ? json_decode(input('post.attribute'),true) : [];
                    if(!empty($attributes) && is_array($attributes)){
                        foreach($attributes as $attr){
                            $attribute = new BusinessToGoodsAttributes();
                            $attribute->title = $attr['title'];
                            $attribute->price = $attr['price'];
                            $attribute->gid = $gid;
                            $attribute->pic = '';
                            if(!$attribute->save()){
                                throw new Exception(6);
                            }
                        }
                    }
                    Db::commit();
                    $output_json = ['status' => 1];
                    return $this->response($output_json, 'json', 200);
                }catch (Exception $e){
                    Db::rollback();
                    return $this->response(['status' => $e->getMessage()], 'json', 200);
                }
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
                if (input('?post.photo')&&(input('post.photo')!=''||input('post.photo')!=null)) {
                    $content = [
                        'pic' => input('post.photo'),
                        'bg' => input('post.photo1'),
                        'name' => input('post.name'),
                        'phone' => input('post.phone'),
                        'device_id' => input('post.device_id'),
                        'start_hour' => input('post.start_hour'),
                        'start_min' => input('post.start_min'),
                        'end_hour' => input('post.end_hour'),
                        'end_min' => input('post.end_min'),
                        'cpc' => input('post.cpc'),
                    ];
                } else {
                    $content = [
                        'name' => input('post.name'),
                        'phone' => input('post.phone'),
                        'device_id' => input('post.device_id'),
                        'start_hour' => input('post.start_hour'),
                        'start_min' => input('post.start_min'),
                        'end_hour' => input('post.end_hour'),
                        'end_min' => input('post.end_min'),
                        'cpc' => input('post.cpc'),
                        'bg' => input('post.photo1')
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
