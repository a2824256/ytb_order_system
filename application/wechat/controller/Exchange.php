<?php
/**
 * Created by PhpStorm.
 * User: yangxixuan
 * Date: 2018/11/25
 * Time: 21:21
 */

namespace app\wechat\controller;


use app\wechat\model\BusinessCurrency;
use think\Controller;

class Exchange extends Controller
{

    /**
     * 调用聚合接口凭证
     */
    public $key = 'fb73be33de192b9d608a7f07f7f5864f';

    /**
     * 调用的接口
     */
    public $url = 'http://op.juhe.cn/onebox/exchange/currency';

    /**
     * 兑换表实例
     */
    private $_businessCurrency;

    public function __construct(BusinessCurrency $businessCurrency)
    {
        $this->_businessCurrency = $businessCurrency;
    }

    /**
     * 定时任务
     * 获取汇率
     */
    public function getExchange(){
        $from = input('get.from');
        $to = input('get.to');
        $key = $this->key;
        $url = $this->url.'?from='.$from.'&to='.$to.'&key='.$key;
        $result = file_get_contents(str_replace("amp;","",$url));
        $result = json_decode($result,true);
            if($result['error_code'] === 0){
                //兑换数组
                foreach($result['result'] as $val){
                    $BusinessCurrency = $this->_businessCurrency->getDetailByFromAndTo($val['currencyF'],$val['currencyT']);
                    if($BusinessCurrency){
                        $BusinessCurrency->currencyf = $val['currencyF'];
                        $BusinessCurrency->currencyf_name = $val['currencyF_Name'];
                        $BusinessCurrency->currencyt = $val['currencyT'];
                        $BusinessCurrency->currencyt_name = $val['currencyT_Name'];
                        $BusinessCurrency->currencyfd = $val['currencyFD'];
                        $BusinessCurrency->exchange = $val['exchange'];
                        $BusinessCurrency->result = $val['result'];
                        $BusinessCurrency->update_time = $val['updateTime'];
                        $BusinessCurrency->save();
                    }else{
                        $BusinessCurrency = new BusinessCurrency();
                        $BusinessCurrency->currencyf = $val['currencyF'];
                        $BusinessCurrency->currencyf_name = $val['currencyF_Name'];
                        $BusinessCurrency->currencyt = $val['currencyT'];
                        $BusinessCurrency->currencyt_name = $val['currencyT_Name'];
                        $BusinessCurrency->currencyfd = $val['currencyFD'];
                        $BusinessCurrency->exchange = $val['exchange'];
                        $BusinessCurrency->result = $val['result'];
                        $BusinessCurrency->update_time = $val['updateTime'];
                        $BusinessCurrency->save();
                    }
                }
            }
    }

}