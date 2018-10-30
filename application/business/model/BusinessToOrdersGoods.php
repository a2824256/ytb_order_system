<?php
namespace app\business\model;

use think\Model;

class BusinessToOrdersGoods extends Model
{
    protected $autoWriteTimestamp = 'datetime';
    protected $table = 'business_to_orders_goods';
}