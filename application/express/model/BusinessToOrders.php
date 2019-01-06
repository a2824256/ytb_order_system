<?php
namespace app\express\model;

use think\Model;

class BusinessToOrders extends Model
{

    protected $pk = 'bid';

    protected $table = 'business_to_orders';

    protected $autoWriteTimestamp = 'datetime';

    /**
     * 获取订单实例
     */
    public function getModelById($oid){
        return BusinessToOrders::where(['oid' => $oid])->find();
    }

    /**
     * 获取订单实例
     */
    public function getModelByOrderId($orderId){
        return BusinessToOrders::join('user','user.uid = business_to_orders.uid')->where(['order_number' => $orderId])->field('user.openid,business_to_orders.*')->find();
    }
}