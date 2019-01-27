<?php
/**
 * Created by PhpStorm.
 * User: yangxixuan
 * Date: 2018/11/25
 * Time: 21:21
 */

namespace app\mini\controller;

use app\mini\controller\Api;
use think\Db;
use think\Response;

class Goods extends Api
{

    /**
     * 商品列表
     */
    public function goods(){
        switch ($this->method) {
            case 'get':
                $bid = input('get.bid');
                $data = [];
                $hot = [];
                $recommend = [];
                $business = Db::table('business_account')->where(['bid' => $bid])->field('recommend')->find();
                $classes = Db::table('business_to_goods_classifications')->where(['bid' => $bid])->field('name,cid')->order('weight desc')->select();
                foreach($classes as $c=>$ify){
                    $data[$c] = $ify;
                    $goods_list = Db::table('business_to_goods')->where(['cid' => $ify['cid']])->field('name,price,pic,cid,gid,is_recommend')->select();
                    $data[$c]['goods_list'] = !empty($goods_list) ? $goods_list : [];
                    foreach($data[$c]['goods_list'] as $g => &$good){
                        $good['attribute'] =  Db::table('business_to_goods_attributes')->where(['gid' => $good['gid'], 'deleted' => 0])->field('id,gid,title,price')->select();
                        if($data[$c]['goods_list'][$g]['is_recommend'] === 1){
//                        //商家置顶
                            array_push($recommend,$data[$c]['goods_list'][$g]);
                        }
                        foreach($good['attribute'] as &$attr){
                            $attr['pic'] = !empty($attr['pic']) ? $attr['pic'] : $good['pic'];
                        }
                    }
                }
                //热门
                $hot = Db::table('business_to_goods')->where(['bid' => $bid])->field('name,price,pic,cid,gid,is_recommend')->order('sell_quantity desc,create_time desc')->limit(5)->select();
                //热门商品属性赋值
                foreach ($hot as &$h) {
                    $h['attribute'] = Db::table('business_to_goods_attributes')->where(['gid' => $h['gid'], 'deleted' => 0])->field('id,gid,title,price')->select();
                }
                $result['goods'] = $data;
                $result['hot'] = $hot;
                $result['recommend'] = $business['recommend'] === 1 ? $recommend : [];
                return Response::create(['code' => 200,'status' => 1,'msg' => '操作成功','data' => $result], 'json', 200);
        }
    }

    /**
     * 商品搜索
     */
    public function search(){
        switch ($this->method) {
            case 'get':
                $bid = input('get.bid');
                $keyword = input('get.keyword');
                if(!$keyword){
                    return Response::create(['code' => 402,'status' => 0,'msg' => '关键字为空'], 'json', 200);
                }
                $goods = Db::table('business_to_goods')->where(['bid' => $bid])->where(['name' => ['like',"%$keyword%"]])->field('name,price,pic,cid,gid,is_recommend')->select();
                foreach($goods as $g => &$good){
                    $good['attribute'] =  Db::table('business_to_goods_attributes')->where(['gid' => $good['gid'], 'deleted' => 0])->field('id,gid,title,price')->select();
                    foreach($good['attribute'] as &$attr){
                        $attr['pic'] = !empty($attr['pic']) ? $attr['pic'] : $good['pic'];
                    }
                }
                return Response::create(['code' => 200,'status' => 1,'msg' => '操作成功','data' => $goods], 'json', 200);
        }
    }

}