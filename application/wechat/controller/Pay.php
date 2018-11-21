<?php
/**
 * Created by PhpStorm.
 * User: yangxixuan
 * Date: 2018/11/20
 * Time: 22:02
 */

namespace app\wechat\controller;

use app\wechat\model\BusinessToOrders;
use think\Controller;
use think\Db;
use think\Loader;
use think\controller\Rest;
use think\Request;

class Pay extends Controller
{

    /**
     * @var string 支付回调地址
     */
    private $notifyUrl = 'http://www.szfengyuecheng.com/wechat/pay/notify';

    /**
     * @var string 订单详情数据
     */
    private $order;

    /**
     * @var array 支付SDK
     */
    private $jsApiParameters = [];

    /**
     * @var string 订单表实例
     */
    private $_businessToOrders;

    /**
     * 初始化
     */
    public function __construct(BusinessToOrders $businessToOrders)
    {
        $this->_businessToOrders = $businessToOrders;
        //引入WxPayPubHelper
        Loader::import('WxPayPubHelper/WxPayPubHelper');
        parent::__construct();
    }

    /**
     * 支付界面
     */
    public function getPayOrder(){
        if(!Request::instance()->isGet()){
            exit('请求方式错误');
        }
        $orderId = input('get.orderId');
        $this->checkParams($orderId);
        $this->getParams();
        return $this->fetch('pay', ['jsApiParameters'=>$this->jsApiParameters,'successPayUrl' => 'www1','cancelUrl'=>'www2']);
    }

    /**
     * 检测参数并赋值
     */
    private function checkParams($orderId){
        if(empty($orderId))
            exit('订单ID不能为空');

        $order = $this->_businessToOrders->getModelByOrderId($orderId);
        $order->total_price = '999999999999999999999';
        try{
            if(!$order->save()){
                var_dump($order->getError());
            }
        }catch (\Exception $e){
            var_dump($e->getMessage());
            die;

        }

        if(!$order)
            exit('订单不存在，请检查订单号码是否有误');
        $this->order = $order;
    }

    /**
     * 获取支付参数
     */
     private function getParams()
    {
        //使用jsapi接口
        $jsApi = new \JsApi_pub();
        // //=========步骤1：网页授权获取用户openid============
        // //通过code获得openid
        // if (!isset($_GET['code']))
        // {
        //     //触发微信返回code码
        //     $url = $jsApi->createOauthUrlForCode(C('WxPayConf_pub.JS_API_CALL_URL'));
        //     Header("Location: $url");
        // }else
        // {
        //     //获取code码，以获取openid
        //     $code = $_GET['code'];
        //     $jsApi->setCode($code);
        //     $openid = $jsApi->getOpenId();
        // }

        //=========步骤2：使用统一支付接口，获取prepay_id============
        //使用统一支付接口
        $unifiedOrder = new \UnifiedOrder_pub();
        //设置统一支付接口参数
        //设置必填参数
        //appid已填,商户无需重复填写
        //mch_id已填,商户无需重复填写
        //noncestr已填,商户无需重复填写
        //spbill_create_ip已填,商户无需重复填写
        //sign已填,商户无需重复填写
        $unifiedOrder->setParameter("openid",$this->order->openid);//商品描述
        $unifiedOrder->setParameter("body","外卖订餐");//商品描述
        //自定义订单号，此处仅作举例

        $unifiedOrder->setParameter("out_trade_no",$this->order->order_number);//商户订单号
        $unifiedOrder->setParameter("total_fee", $this->order->total_price);//总金额
        $unifiedOrder->setParameter("notify_url",$this->notifyUrl);//通知地址
        $unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
        //非必填参数，商户可根据实际情况选填
        //$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
        //$unifiedOrder->setParameter("device_info","XXXX");//设备号
        //$unifiedOrder->setParameter("attach","XXXX");//附加数据
        //$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
        //$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间
        //$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
        //$unifiedOrder->setParameter("openid","XXXX");//用户标识
        //$unifiedOrder->setParameter("product_id","XXXX");//商品ID

        $prepay_id = $unifiedOrder->getPrepayId();
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);
        $this->jsApiParameters = $jsApi->getParameters();
    }

    /**
     * 支付回调接口
     */
    public function notify()
    {
        //使用通用通知接口
        $notify = new \Notify_pub();

        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $notify->saveData($xml);

        //验证签名，并回应微信。
        //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
        //微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
        //尽可能提高通知的成功率，但微信不保证通知最终能成功。
        if($notify->checkSign() == FALSE){
            $notify->setReturnParameter("return_code","FAIL");//返回状态码
            $notify->setReturnParameter("return_msg","签名失败");//返回信息
        }else{
            $notify->setReturnParameter("return_code","SUCCESS");//设置返回码
        }
        //$returnXml = $notify->returnXml();
        // echo $returnXml;


        //==商户根据实际情况设置相应的处理流程，此处仅作举例=======

        //以log文件形式记录回调信息

        /* $log_name= __ROOT__."/Public/notify_url.log";//log文件路径
         self::logResult($log_name,"【接收到的notify通知】:\n".$xml."\n");*/

        if($notify->checkSign() == TRUE)
        {
            if ($notify->data["return_code"] == "FAIL") {
                //此处应该更新一下订单状态，商户自行增删操作
                self::logResult("【通信出错】:".$xml);
            }
            elseif($notify->data["result_code"] == "FAIL"){
                //此处应该更新一下订单状态，商户自行增删操作
                self::logResult("【业务出错】:".$xml);
            }
            else{
                $orderNo = $notify->data['out_trade_no'];
                $this->checkParams($orderNo);
                if($this->order->status == 0){
                    //如果订单未支付则进入此逻辑
                    Db::transaction();
                    try{
                        $this->order->status = 1;
                        if(!$this->order->save()){
                            throw new \Exception("openid:{$this->order->openid},error:{$this->order->getError()}");
                        }
                        //此处应该更新一下订单状态，商户自行增删操作
                        self::logResult("【支付成功】:".$xml);
                        Db::commit();
                    }catch(\Exception $e){
                        Db::rollback();
                        self::logResult("【异常报错信息】:".$e->getMessage());
                    }
                }
            }
            //商户自行增加处理流程,
            //例如：更新订单状态
            //例如：数据库操作
            //例如：推送支付完成信息
        }
    }

    /**
     * 写入支付日志
     * @param $text 响应文本
     */
    public static function logResult($text){
        $date = date('Ym');
        $filePath = __DIR__.'/../log/'.$date.'_pay_log.txt';
        if(!file_exists($filePath)){
            $fp = fopen($filePath, "w") or die("Unable to open file!");
        }else{
            $fp = fopen($filePath, "a") or die("Unable to open file!");
        }
        $json = json_encode($text,JSON_UNESCAPED_UNICODE)."\n";
        fwrite($fp, $json);
        fclose($fp);
        exit;
    }


}