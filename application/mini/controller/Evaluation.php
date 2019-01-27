<?php
/**
 * Created by PhpStorm.
 * User: yangxixuan
 * Date: 2019/1/1
 * Time: 16:13
 */

namespace app\mini\controller;

use app\mini\controller\Api;
use think\Db;
use think\Response;

class Evaluation extends Api
{
    /**
     * 限制数量
     * @var int
     */
    private $limit = 10;

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
                $list = Db::table('evaluation')->alias('e')
                    ->join('user_mini_info umi','umi.uid = e.uid')
                    ->field('umi.nickname,umi.avatarUrl,e.*')
                    ->where(['bid' => $param['bid']])->page($param['page'],$this->limit)
                    ->order('e.create_time desc')
                    ->select();
                foreach($list as &$val){
                    $val['pic_arr'] = $val['pic_arr'] ?  json_decode($val['pic_arr'],true) : [];
                }
                return Response::create(['code' => 200,'status' => 1,'msg' => '操作成功','data' => $list], 'json', 200);
        }
    }

    /**
     * 添加评价
     */
    public function add(){
        switch ($this->method){
            case 'post':
                $user = $this->getUser();
                if(empty($user)){
                    return Response::create(['code' => 401,'status' => 0, 'msg' => '用户不存在'], 'json', 200);
                }
                $user_evaluation = DB::table('evaluation')->where(['order_number' =>  trim(input('post.order_number')),'uid' => $user['uid']])->find();
                if(!empty($user_evaluation)){
                    return Response::create(['code' => 405,'status' => 0, 'msg' => '你已评价无须重复评价'], 'json', 200);
                }
                $data = [
                    'bid' => trim(input('post.bid')),
                    'content' => htmlspecialchars_decode(input('post.content')),
                    'pic_arr' => trim(input('post.pic_arr')),
                    'uid' => $user['uid'],
                    'business_star' => trim(input('post.business_star')),
                    'deliveryman_star' => trim(input('post.deliveryman_star')),
                    'service_star' => trim(input('post.service_star')),
                    'order_number' => trim(input('post.order_number')),
                ];
                if(!Db::table('evaluation')->insert($data)){
                    return Response::create(['code' => 405,'status' => 0,'msg' => '评价失败'], 'json', 200);
                }
                Db::table('order')->where(['order_number' => $data['order_number']])->update(['step' => 6]);
                return Response::create(['code' => 200,'status' => 1,'msg' => '操作成功'], 'json', 200);
        }
    }
}