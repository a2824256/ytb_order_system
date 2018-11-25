<?php
/**
 * Created by PhpStorm.
 * User: yangxixuan
 * Date: 2018/11/25
 * Time: 21:21
 */

namespace app\wechat\controller;


use app\wechat\model\Currency;
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
    private $_currency;

    public function __construct(Currency $currency)
    {
        $this->_currency = $currency;
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
                    $Currency = $this->_currency->getDetailByFromAndTo($from,$to);
                    if($Currency){
                        $Currency->currencyf = $val['currencyF'];
                        $Currency->currencyf_name = $val['currencyF_Name'];
                        $Currency->currencyt = $val['currencyT'];
                        $Currency->currencyt_name = $val['currencyT_Name'];
                        $Currency->currencyfd = $val['currencyFD'];
                        $Currency->exchange = $val['exchange'];
                        $Currency->result = $val['result'];
                        $Currency->updateTime = $val['updateTime'];
                        $Currency->save();
                    }else{
                        $Currency = new Currency();
                        $Currency->currencyf = $val['currencyF'];
                        $Currency->currencyf_name = $val['currencyF_Name'];
                        $Currency->currencyt = $val['currencyT'];
                        $Currency->currencyt_name = $val['currencyT_Name'];
                        $Currency->currencyfd = $val['currencyFD'];
                        $Currency->exchange = $val['exchange'];
                        $Currency->result = $val['result'];
                        $Currency->updateTime = $val['updateTime'];
                        $Currency->save();
                    }
                }
            }
    }

}