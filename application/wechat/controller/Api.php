<?php
/**
 * Created by PhpStorm.
 * User: AlexLeung
 * Date: 2018/11/7
 * Time: 7:51
 */

namespace app\wechat\controller;

use \think\controller\Rest;
use \think\Response;
use \app\common\model\BusinessAccount;
use \app\wechat\model\User;
use \app\business\model\BusinessToGoods;
use \app\business\model\BusinessToGoodsClassifications;

class Api extends Rest
{
    private $header = ["Access-Control-Allow-Method"=>"*","Access-Control-Allow-Origin"=>"http://127.0.0.1:8000","Access-Control-Allow-Credentials"=>true,"Access-Control-Allow-Headers"=>"Origin, X-Requested-With, Content-Type, Accept"];
    private $output_json_template = [
        'status' => 0
    ];

    public function businessList()
    {
        switch ($this->method) {
            case 'get':
                $bussinessList = new BusinessAccount();
                $json['data'] = $bussinessList->field('name,phone,start_hour,start_min,end_hour,end_min,pic,cpc,bid')->select();
                foreach ($json['data'] as $key => $value){
                    if($json['data'][$key]['start_hour']<10){
                        $json['data'][$key]['start_hour'] = "0".$json['data'][$key]['start_hour'];
                    }
                    if($json['data'][$key]['start_min']<10){
                        $json['data'][$key]['start_min'] = "0".$json['data'][$key]['start_min'];
                    }
                    if($json['data'][$key]['end_hour']<10){
                        $json['data'][$key]['end_hour'] = "0".$json['data'][$key]['end_hour'];
                    }
                    if($json['data'][$key]['end_min']<10){
                        $json['data'][$key]['end_min'] = "0".$json['data'][$key]['end_min'];
                    }
                }
                return Response::create($json, 'json', 200,$this->header);
        }
    }

    public function Login()
    {
        switch ($this->method) {
            case 'post':
                $user = new User();
                $json = [];
                $res = $user->where(['telephone'=>input('post.telephone'),'password'=>input('post.password')])->field('uid,user_name,address,post_code,telephone')->find();
                if($res){
                    $json['status'] = true;
                    $json['user_info'] = $res;
                }else{
                    $json['status'] = false;
                }

//                header('Access-Control-Allow-Origin:*');
//                header('Access-Control-Allow-Methods:*');
//                header('Access-Control-Allow-Headers:*');
//                header('Access-Control-Allow-Credentials:false');
                return Response::create($json, 'json', 200,$this->header);
        }
    }

    public function goodsAndClassList(){
        switch ($this->method) {
            case 'get':
                $bussinessList = new BusinessAccount();
                $json['business_info'] = $bussinessList->where(['bid'=>input('get.bid')])->field('name,phone,address,start_hour,start_min,end_hour,end_min,pic,cpc,bid')->find();
                if($json['business_info']['start_hour']<10){
                    $json['business_info']['start_hour'] = "0".$json['business_info']['start_hour'];
                }
                if($json['business_info']['start_min']<10){
                    $json['business_info']['start_min'] = "0".$json['business_info']['start_min'];
                }
                if($json['business_info']['end_hour']<10){
                    $json['business_info']['end_hour'] = "0".$json['business_info']['end_hour'];
                }
                if($json['business_info']['end_min']<10){
                    $json['business_info']['end_min'] = "0".$json['business_info']['end_min'];
                }
                $goods = new BusinessToGoods();
                $class = new BusinessToGoodsClassifications();
                $goods_list = $goods->where(['bid'=>input('get.bid')])->field('name,price,pic,cid,gid')->select();
                $classes = $class->where(['bid'=>input('get.bid')])->field('name,cid')->select();
                $json['goods'] = [];
                $json['class_title'] = [];
                foreach ($classes as $key => $value){
                    $json['class_title'][]['title'] = $value['name'];
                    $json['goods'][$value['cid']] = [];
//                    $json['classes'][$key]['goods'] = [];
                    foreach ($goods_list as $value2){
                        if($value2['cid'] == $value['cid']){
                            $json['goods'][$value['cid']][] = $value2;
                        }
                    }
                }
                $final_json['data'] = $json;

                return Response::create($final_json, 'json', 200,$this->header);
        }
    }
}