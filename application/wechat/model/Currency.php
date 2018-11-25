<?php
/**
 * Created by PhpStorm.
 * User: yangxixuan
 * Date: 2018/11/25
 * Time: 21:35
 */

namespace app\wechat\model;


use think\Model;

class Currency extends Model
{
    protected $pk = 'id';
    protected $table = 'currency';
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 根据form和to获取转换汇率数据
     */
    public function getDetailByFromAndTo($from,$to){
        return Currency::where(['from' => $from,'to' => $to])->find();
    }
}