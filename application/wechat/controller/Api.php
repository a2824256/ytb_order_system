<?php
/**
 * Created by PhpStorm.
 * User: AlexLeung
 * Date: 2018/11/7
 * Time: 7:51
 */

namespace app\wechat\controller;

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

    public function BusinessList()
    {
        switch ($this->method) {
            case 'get':
                $bussinessList = new BusinessAccount();
                $json['data'] = $bussinessList->field('name,phone,start_hour,start_min,end_hour,end_min,pic,cpc')->select();
                return $this->response($json, 'json', 200);
        }
    }
}