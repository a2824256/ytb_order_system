<?php
namespace app\business\model;

use think\Model;

class BusinessToOrders extends Model
{
    protected $pk = 'bid';
    protected $table = 'business_to_orders';
    protected $autoWriteTimestamp = 'datetime';
    public function goods(){
        return $this->hasMany('BusinessToOrdersGoods','order_number');
    }
    public function getStatusAttr($value)
    {
        //TODO 字段需调整
        $status = [0=>'Unpaid.',1=>'WeChat payment.',2=>'Cash on delivery.'];
        return $status[$value];
    }
}