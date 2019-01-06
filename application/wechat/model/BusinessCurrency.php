<?php
/**
 * Created by PhpStorm.
 * User: yangxixuan
 * Date: 2018/11/25
 * Time: 21:35
 */

namespace app\wechat\model;


use think\Model;

class BusinessCurrency extends Model
{
    protected $pk = 'id';
    protected $table = 'business_currency';

    /**
     * 根据form和to获取转换汇率数据
     */
    public function getDetailByFromAndTo($from,$to){
        return BusinessCurrency::where(['currencyf' => $from,'currencyt' => $to])->find();
    }
}