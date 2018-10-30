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
        $status = [0=>'User canceled.',1=>'Business confirmed.',2=>'Business canceled.',3=>'Completed.'];
        return $status[$value];
    }
}