<?php
/**
 * Created by PhpStorm.
 * User: yangxixuan
 * Date: 2018/11/20
 * Time: 22:02
 */

namespace app\mini\controller;

use app\mini\controller\Api;
use EasyWeChat\Factory;
use think\Db;
use think\Loader;
use \think\Response;

class Pay extends Api
{

    /**
     * @var string 支付回调地址
     */
    private $notifyUrl = 'https://www.szfengyuecheng.com/mini/pay/notify';

    /**
     * @var string 订单详情数据
     */
    private $order;

    /**
     * @var array 支付SDK
     */
    private $jsApiParameters = [];

    /**
     * 错误信息搜集
     */
    private $errorPay = '';

    /**
     * 初始化
     */
    public function __construct()
    {
        //引入WxPayPubHelper
        Loader::import('WxPayPubHelperApplets/WxPayPubHelper');
        parent::__construct();
    }

    /**
     * 支付界面
     */
    public function getPayOrder()
    {
        switch ($this->method){
            case 'post':
                $user = $this->getUser();
                if(empty($user)){
                    return Response::create(['code' => 401,'status' => 0, 'msg' => '用户不存在'], 'json', 200);
                }
                $order_number = input('post.order_number');
                if(!$this->checkParams($order_number)){
                    return Response::create(['code' => 402,'status' => 0, 'msg' => '参数不正确'], 'json', 200);
                }
                if(!$this->getParams()){
                    return Response::create(['code' => 405,'status' => 0, 'msg' => $this->errorPay], 'json', 200);
                }
                return Response::create(['code' => 200,'status' => 1, 'msg' => '操作成功','data' => json_decode($this->jsApiParameters,true)], 'json', 200);
        }
    }

    /**
     * 检测参数并赋值
     */
    private function checkParams($order_number)
    {
        if (empty($order_number)) return false;
        $order = Db::table('order')->join('user_mini_info','user_mini_info.uid = order.uid')->where(['order_number' => $order_number])->field('user_mini_info.order_openid,order.*')->find();
        if (!$order) return false;
        $this->order = json_decode(json_encode($order));
        return true;
    }

    /**
     * 获取支付参数
     */
    private function getParams()
    {
        //五分钟支付限制
        if (time() - strtotime($this->order->create_time) > 300) {
            $this->errorPay = '订单支付时间超过5分钟';
            return false;
        }
        //获得英镑汇率
//        $Currency = $this->_businessCurrency->getDetailByFromAndTo('GBP','CNY');
        //最终汇率
//        $rate = bcadd($Currency->result,0.15,4);
//        $rate = 9;
        //金额向上取整单位（元）
//        $total_price = ceil(bcmul($this->order->total_price_cny,$rate,2));
        $total_price = $this->order->total_price_cny;
        //测试专用
        $total_price = 1;
        //使用jsapi接口
        try {
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
            $unifiedOrder->setParameter("openid", $this->order->order_openid);//商品描述
            $unifiedOrder->setParameter("body", "外卖订餐");//商品描述
            //自定义订单号，此处仅作举例
            $unifiedOrder->setParameter("out_trade_no", $this->order->order_number);//商户订单号
            $unifiedOrder->setParameter("total_fee", $total_price);//总金额
            $unifiedOrder->setParameter("notify_url", $this->notifyUrl);//通知地址
            $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
            //非必填参数，商户可根据实际情况选填
            //$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
            //$unifiedOrder->setParameter("device_info","XXXX");//设备号
            //$unifiedOrder->setParameter("attach","XXXX");//附加数据
//            $unifiedOrder->setParameter("time_start",date("YmdHis",strtotime($this->order->create_time)));//交易起始时间
//            $unifiedOrder->setParameter("time_expire",date('YmdHis',strtotime($this->order->create_time) + 60 * 5));//交易结束时间
            //$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
            //$unifiedOrder->setParameter("openid","XXXX");//用户标识
            //$unifiedOrder->setParameter("product_id","XXXX");//商品ID
            $prepay_id = $unifiedOrder->getPrepayId();
            //=========步骤3：使用jsapi调起支付============
            $jsApi->setPrepayId($prepay_id);
            $this->jsApiParameters = $jsApi->getParameters();
            return true;
        } catch (\Exception $e) {
            $this->errorPay = $e->getMessage();
            return false;
        }
    }

    /**
     * 支付回调接口
     */
    public function notify()
    {
        //使用通用通知接口
        $notify = new \Notify_pub();
        //存储微信的回调
        $xml = file_get_contents('php://input');
//        $xml = "<xml><appid><![CDATA[wx007296fc9a7d315f]]></appid><bank_type><![CDATA[CFT]]></bank_type><cash_fee><![CDATA[1]]></cash_fee><fee_type><![CDATA[CNY]]></fee_type><is_subscribe><![CDATA[Y]]></is_subscribe><mch_id><![CDATA[1517605751]]></mch_id><nonce_str><![CDATA[n2zwk5unl1365d3axvg018via68biibc]]></nonce_str><openid><![CDATA[ohR9-5uW69zvsQPzBGpD47rWct9g]]></openid><out_trade_no><![CDATA[2019028]]></out_trade_no><result_code><![CDATA[SUCCESS]]></result_code><return_code><![CDATA[SUCCESS]]></return_code><sign><![CDATA[21400EA1AA389F07BF78E09A6C6343BE]]></sign><time_end><![CDATA[20181128200152]]></time_end><total_fee>1</total_fee><trade_type><![CDATA[JSAPI]]></trade_type><transaction_id><![CDATA[4200000198201811283819414168]]></transaction_id></xml>";
        $notify->saveData($xml);
        //验证签名，并回应微信。
        //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
        //微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
        //尽可能提高通知的成功率，但微信不保证通知最终能成功。
        if ($notify->checkSign() == FALSE) {
            $notify->setReturnParameter("return_code", "FAIL");//返回状态码
            $notify->setReturnParameter("return_msg", "签名失败");//返回信息
        } else {
            $notify->setReturnParameter("return_code", "SUCCESS");//设置返回码
        }
        //$returnXml = $notify->returnXml();
        // echo $returnXml;


        //==商户根据实际情况设置相应的处理流程，此处仅作举例=======

        //以log文件形式记录回调信息

        /* $log_name= __ROOT__."/Public/notify_url.log";//log文件路径
         self::logResult($log_name,"【接收到的notify通知】:\n".$xml."\n");*/

        if ($notify->checkSign() == TRUE) {
            if ($notify->data["return_code"] == "FAIL") {
                //此处应该更新一下订单状态，商户自行增删操作
                self::logResult("【通信出错】:" . $xml);
            } elseif ($notify->data["result_code"] == "FAIL") {
                //此处应该更新一下订单状态，商户自行增删操作
                self::logResult("【业务出错】:" . $xml);
            } else {
                $orderNo = $notify->data['out_trade_no'];
                $this->checkParams($orderNo);
                if ($this->order->status == 0) {
                    //支付成功后订单未改支付状态则进入此逻辑
                    Db::startTrans();
                    try {
                        //增加销售数量
                        $order_param = Db::table('order')->where('order_number', $this->order->order_number)->field('json')->find();
                        $goods = json_decode($order_param['json'], true);
                        foreach ($goods as $good) {
                            Db::table('business_to_goods')->where(['gid' => $good['gid']])->setInc('sell_quantity', $good['number']);
                        }
                        //修改支付状态
                        $result = Db::table('order')->where(['order_number' => $this->order->order_number])->update(['status' => 1]);
                        if (!$result) {
                            throw new \Exception("order_openid:{$this->order->order_openid},error:{$this->order->getError()}");
                        }
                        Db::commit();
                        //打印订单
                        $printer = new \app\printer\controller\Api();
                        $printerResult = $printer->printOrder($this->order->order_number);
                        if ($printerResult != 'OK') {
                            self::logResult("【打印信息报错】:" . $this->order->order_openid);
                        }
                        //推送模板消息  union_id未与微信openid打通
//                        self::sendTempMsg($this->order->order_openid, 1, $this->order->order_number);
//                        self::sendTempMsg($this->order->order_openid, 3, $this->order->order_number);
//                        self::send2Deliveryman($this->order->order_number);
                        //此处应该更新一下订单状态，商户自行增删操作
                        self::logResult("【order_openid】:" . $this->order->order_openid);
                        self::logResult("【order_number】:" . $this->order->order_number);
                        self::logResult("【支付成功】:" . $xml);
                    } catch (\Exception $e) {
                        Db::rollback();
                        self::logResult("【异常报错信息】:" . $e->getMessage());
                    }
                }
            }
            //商户自行增加处理流程,
            //例如：更新订单状态
            //例如：数据库操作
            //例如：推送支付完成信息
        }
    }

    public function test(){
        self::logResult(1);
    }

    /**
     * 写入支付日志
     * @param $text 响应文本
     */
    private static function logResult($text)
    {
        $date = date('Ym');
        $filePath = __DIR__ . '/../log/pay_log_' . $date . '.txt';
        if (!file_exists($filePath)) {
            $fp = fopen($filePath, "w") or die("Unable to open file!");
        } else {
            $fp = fopen($filePath, "a") or die("Unable to open file!");
        }
        $json = json_encode($text, JSON_UNESCAPED_UNICODE) . "\n";
        fwrite($fp, $json);
        fclose($fp);
    }

    /**
     * @param $openid openid
     * @param $type 消息类型，1：用户支付成功，2：订单状态改变，3:商家新订单提醒，4：骑手派送提醒
     * @param $orderNumber 订单号
     */
    public static function sendTempMsg($openid, $type, $orderNumber)
    {
        $config = [
            'app_id' => 'wx007296fc9a7d315f',
            'secret' => '50ddb0815ab75971d407f4218222675c',
            'response_type' => 'array',
        ];
        $app = Factory::officialAccount($config);
        $template_id = '';
        $data = [];
        $order_info = Db::table('order')->where(['order_number' => $orderNumber])->find();
        $business = Db::table('business_account')->where(['bid' => $order_info['bid']])->find();
        $price = $order_info['total_price'] / 100;
        $total_price_cny = $order_info['total_price_cny'] / 100;
        $good = Db::table('order_goods')->where(['order_number' => $orderNumber])->find();
        switch ($type) {
            case 1:
                $template_id = '1f7cb5Q180yncUHJEMKzeMuEh_fvA1-OB1D_twLB10I';
                $data = [
                    'first' => '您的订单下单成功',
                    'keyword1' => $business['name'],
                    'keyword2' => $order_info['create_time'],
                    'keyword3' => $good['good_name'] . "等商品",
                    'keyword4' => $price . "磅（折合人民币" . $total_price_cny . "元）",
                    'remark' => '约20分送达，请保持手机畅通',
                ];
                break;
            case 3:
                if ($business['manager'] == NULL) {
                    return 0;
                }
                $template_id = '0sXUoZ3cLosExmWEopcYXkk2jUHqo5lv6Dwi9ALzIOI';
                $data = [
                    'first' => '您有一份新的订单,编号为' . $order_info['order_number'],
                    'keyword1' => $good['good_name'] . "等商品",
                    'keyword2' => $price . '磅',
                    'keyword3' => $order_info['user_name'] . ',' . $order_info['user_telephone'] . ',' . $order_info['user_address'] . ',' . $order_info['user_post_code'],
                    'keyword4' => '微信支付',
                    'keyword5' => '无',
                    'remark' => '下单时间：' . $order_info['create_time'],
                ];
                break;
            default:
                return 0;
        }
        if ($type == 1) {
            $final_data = [
                'touser' => $openid,
                'template_id' => $template_id,
                'data' => $data,
            ];
        } elseif ($type == 3) {
            $final_data = [
                'touser' => $business['manager'],
                'template_id' => $template_id,
                'data' => $data,
                "url" => "https://www.szfengyuecheng.com/wechatbusiness/index/index/orderNumber/" . $orderNumber . "/openid/" . $business['manager'],
            ];
        }else{
            return 0;
        }
        $app->template_message->send($final_data);
        return 1;
    }

    private static function send2Deliveryman($orderNumber)
    {
        $config = [
            'app_id' => 'wx007296fc9a7d315f',
            'secret' => '50ddb0815ab75971d407f4218222675c',
            'response_type' => 'array',
        ];
        $app = Factory::officialAccount($config);
        $order_info = Db::table('order')->where(['order_number' => $orderNumber])->find();
        $business = Db::table('business_account')->where(['bid' => $order_info['bid']])->find();
        $price = $order_info['total_price'] / 100;
        $total_price_cny = $order_info['total_price_cny'] / 100;
        $time = date('Y-m-d H:i:s', strtotime($order_info['create_time'])+ (20 * 60));
        $dman = Db::table('deliveryman')->field('openid')->select();
        foreach ($dman as $key) {
            $temp = [
                'touser' => $key['openid'],
                'template_id' => 'Avn8YCqx4SGAyjdq5gcPmDFpMsx6iNQOtvGm1OOQocE',
                'data' => [
                    'first' => '新订单通知！！！！！！！',
                    'keyword1' => $orderNumber,
                    'keyword2' => "姓名:".$order_info['user_name'] . '  电话:' . $order_info['user_telephone'],
                    'keyword3' => "地址:".$order_info['user_address'] . '  邮编:' . $order_info['user_post_code'],
                    'keyword4' => '微信支付：' . $total_price_cny . '元（折合' . $price . '磅）',
                    'keyword5' => $time,
                    'remark' => '去'.$business['name'].'取餐，行快两步啦柒头！！',
                ],
                "url" => "https://www.szfengyuecheng.com/express/index/index",
            ];
            $app->template_message->send($temp);
        }
    }

}