<?php
/**
 * Created by PhpStorm.
 * User: yangxixuan
 * Date: 2018/11/27
 * Time: 22:41
 */

namespace app\wechat\model;


use think\Model;

class OrderNumber extends Model
{
    protected $pk = 'id';
    protected $table = 'order_number';

    public function getOrderNumber($id){
        return OrderNumber::where(['id' => $id])->find();
    }

}