<?php

namespace app\business\controller;

use \think\controller\Rest;
use \app\common\model\BusinessAccount;
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
                    $business->isUpdate(true)->save(['bid' => $business->bid, 'final_login' => date('Y-m-d H:i:s', time())]);
                    $output_json = $business->visible(['bid'])->toArray();
                    $output_json['status'] = 1;
                    $output_json['acc'] = $account;
                    $output_json['pwd'] = $password;
//                    $output_json['session_account'] = Session::get('business', 'ytb');
                }else{
                    $output_json['status'] = 2;
                }
                return $this->response($output_json, 'json', 200);
        }
    }

    public function orders(){
        switch ($this->method) {
            case 'get':

        }
    }
}
