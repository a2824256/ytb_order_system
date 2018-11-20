<?php
namespace app\wechat\model;

use think\Model;

class BusinessToOrders extends Model
{

    protected $pk = 'bid';

    protected $table = 'business_to_orders';

    protected $autoWriteTimestamp = 'datetime';

    /**
     * 获取订单实例
     */
    public function getModelByOrderId($orderId){
        return BusinessToOrders::where(['order_number' => $orderId])->find();
    }

    public function RelationUser(){
        return $this->hasOne('user','id','uid')->field('openid');
    }

}