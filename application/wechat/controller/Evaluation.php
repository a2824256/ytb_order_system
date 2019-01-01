<?php
/**
 * Created by PhpStorm.
 * User: yangxixuan
 * Date: 2019/1/1
 * Time: 16:13
 */

namespace app\wechat\controller;


use think\controller\Rest;
use think\Response;

class Evaluation extends Rest
{
//    private $header = ["Access-Control-Allow-Method" => "*", "Access-Control-Allow-Origin" => "http://business.szfengyuecheng.com", "Access-Control-Allow-Credentials" => true, "Access-Control-Allow-Headers" => "Origin, X-Requested-With, Content-Type, Accept"];
    private $header = ["Access-Control-Allow-Method" => "*", "Access-Control-Allow-Origin" => "http://127.0.0.1:8000", "Access-Control-Allow-Credentials" => true, "Access-Control-Allow-Headers" => "Origin, X-Requested-With, Content-Type, Accept"];

    /**
     * 限制数量
     * @var int
     */
    const limit = 5;

    /**
     * 评价列表
     */
    public function getList(){
        switch ($this->method){
            case 'get':
                $param = [
                    'bid' => trim(input('get.bid')),
                    'page' => input('get.page') ? input('get.page') : 1,
                ];
                //计算开始偏移量
                $start = ($param['page'] - 1) * limit;
                $evaluationObj = new \app\wechat\model\Evaluation();
                $list = $evaluationObj->where(['bid' => $param['bid']])->limit($start,limit)->select();
                foreach($list as &$val){
                    $val['pic_arr'] = json_decode($val['pic_arr']);
                }
                return Response::create($list, 'json', 200, $this->header);
            case 'post':
                break;
        }
    }

    /**
     * 添加评价
     */
    public function add(){
        switch ($this->method){
            case 'post':
                $evaluationObj = new \app\wechat\model\Evaluation();
                $evaluationObj->bid = trim(input('post.bid'));
                $evaluationObj->content = htmlspecialchars_decode(input('post.content'));
                $evaluationObj->pic_arr = trim(input('post.pic_arr'));
                $evaluationObj->uid = trim(input('post.uid'));
                $evaluationObj->business_star = trim(input('post.business_star'));
                $evaluationObj->deliveryman_star = trim(input('post.deliveryman_star'));
                $evaluationObj->service_star = trim(input('post.service_star'));
                $evaluationObj->order_number = trim(input('post.order_number'));
                if(!$evaluationObj->save()){
                    return Response::create(['errcode' => -1,'errmsg' => '评价失败'], 'json', 200, $this->header);
                }
                return Response::create(['errcode' => 0,'errmsg' => 'ok'], 'json', 200, $this->header);
        }
    }
}