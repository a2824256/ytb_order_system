<?php
namespace app\mini\controller;

use app\mini\controller\Api;
use think\Db;
use think\Response;


class Index extends Api
{

    //限制每页显示的数据数量
    private $limit = 10;
    /**
     * 商家列表
     */
    public function businessList()
    {
        switch ($this->method) {
            case 'get':
                $lng = input('get.lng');
                $lat = input('get.lat');
                $page = input('get.page') ? input('get.page') : 1;
                $w = date('w');
                $json = Db::table('business_account')->alias('ba')
                    ->join('business_time_table bt', 'ba.bid = bt.bid', 'LEFT')
                    ->field('ba.*,bt.time_0,bt.time_1,bt.time_2,bt.time_3,bt.time_4,bt.time_5,bt.time_6')
                    ->where(['ba.status' => 0])
                    ->order("ba.weight DESC")
                    ->select();
                foreach ($json as $key => $value) {
//                  时间处理
                    if ($json[$key]['start_hour'] < 10) {
                        $json[$key]['start_hour'] = "0" . $json[$key]['start_hour'];
                    }
                    if ($json[$key]['start_min'] < 10) {
                        $json[$key]['start_min'] = "0" . $json[$key]['start_min'];
                    }
                    if ($json[$key]['end_hour'] < 10) {
                        $json[$key]['end_hour'] = "0" . $json[$key]['end_hour'];
                    }
                    if ($json[$key]['end_min'] < 10) {
                        $json[$key]['end_min'] = "0" . $json[$key]['end_min'];
                    }
//                  是否营业
                    $time = date("Hi");
                    $json[$key]['time_0'] = empty($json[$key]['time_0']) ? [$json[$key]['start_hour'].$json[$key]['start_min'],$json[$key]['end_hour'].$json[$key]['end_min']] : explode('|',$json[$key]['time_0']);
                    $json[$key]['time_1'] = empty($json[$key]['time_1']) ? [$json[$key]['start_hour'].$json[$key]['start_min'],$json[$key]['end_hour'].$json[$key]['end_min']] : explode('|',$json[$key]['time_1']);
                    $json[$key]['time_2'] = empty($json[$key]['time_2']) ? [$json[$key]['start_hour'].$json[$key]['start_min'],$json[$key]['end_hour'].$json[$key]['end_min']] : explode('|',$json[$key]['time_2']);
                    $json[$key]['time_3'] = empty($json[$key]['time_3']) ? [$json[$key]['start_hour'].$json[$key]['start_min'],$json[$key]['end_hour'].$json[$key]['end_min']] : explode('|',$json[$key]['time_3']);
                    $json[$key]['time_4'] = empty($json[$key]['time_4']) ? [$json[$key]['start_hour'].$json[$key]['start_min'],$json[$key]['end_hour'].$json[$key]['end_min']] : explode('|',$json[$key]['time_4']);
                    $json[$key]['time_5'] = empty($json[$key]['time_5']) ? [$json[$key]['start_hour'].$json[$key]['start_min'],$json[$key]['end_hour'].$json[$key]['end_min']] : explode('|',$json[$key]['time_5']);
                    $json[$key]['time_6'] = empty($json[$key]['time_6']) ? [$json[$key]['start_hour'].$json[$key]['start_min'],$json[$key]['end_hour'].$json[$key]['end_min']] : explode('|',$json[$key]['time_6']);
                    //极端场景连续两天宵夜档
                    $now = $json[$key]["time_$w"];
                    if($now[0] >= $now[1]){
                        $now[1] += 2400;
                    }

                    if ($time >= $now[0] && $time <= $now[1]) {
                        $json[$key]['is_open'] = 1;
                    } else {
                        $json[$key]['is_open'] = 0;
                    }

                    $last_w = $w==0 ? 6 : $w - 1;
                    $last_now = $json[$key]["time_$last_w"];
                    if($last_now[0] >= $last_now[1]){
                        if($time <= $last_now[1] && $time+2400 >= $last_now[0]){
                            $now[0] = $last_now[0];
                            $now[1] = $last_now[1] + 2400;
                            $json[$key]['is_open'] = 1;
                        }
                    }

                    if($now[1] > 2400){
                        $now[1] = '0'.($now[1]-2400);
                    }

                    $json[$key]['start_hour'] =  substr($now[0],0,2);
                    $json[$key]['start_min'] = substr($now[0],2,2);
                    $json[$key]['end_hour'] = substr($now[1],0,2);
                    $json[$key]['end_min'] = substr($now[1],2,2);

                    //相差多少英里
                    $json[$key]['mile'] = '2km';

                    $evaluation = DB::table('evaluation')->where(['bid' => $value['bid']])->field('avg(business_star) as avg_taste_star,avg(deliveryman_star) as avg_deliveryman_star,avg(service_star) as avg_service_star')->find();
                    $evaluation['avg_taste_star'] = round($evaluation['avg_taste_star']);
                    $evaluation['avg_deliveryman_star'] = round($evaluation['avg_deliveryman_star']);
                    $evaluation['avg_service_star'] = round($evaluation['avg_service_star']);
                    $evaluation['result_star'] = round(($evaluation['avg_taste_star'] + $evaluation['avg_deliveryman_star'] + $evaluation['avg_service_star']) / 3);
                    $json[$key]['evaluation'] = $evaluation;
                    $isOpen[] = $json[$key]['is_open'];
                    $weight[] = $json[$key]['weight'];
                }
                array_multisort($isOpen,SORT_DESC,$weight,SORT_DESC,$json);
                $start = ($page - 1) * $this->limit;
                $result['list'] = array_slice($json,$start,$this->limit);
                $result['total'] = count($json);
                return Response::create(['code' => 200,'status' => 1, 'msg' => '操作成功','data' => $result], 'json', 200);
        }
    }

    /**
     * 商家列表
     */
    public function businessListBak()
    {
        switch ($this->method) {
            case 'get':
                $lng = input('get.lng');
                $lat = input('get.lat');
                $page = input('get.page') ? input('get.page') : 1;
                $w = date('w');
                $json = Db::table('business_account')->alias('ba')
                    ->join('business_time_table bt', 'ba.bid = bt.bid', 'LEFT')
                    ->field('ba.*,bt.start_' . $w . ' as start,bt.end_' . $w . ' as end')
                    ->where(['ba.status' => 0])
                    ->order("ba.weight DESC")
                    ->page($page,$this->limit)->select();
                $total = Db::table('business_account')->alias('ba')->where(['ba.status' => 0])->count();
                foreach ($json as $key => $value) {
//                  时间处理
                    if ($json[$key]['start_hour'] < 10) {
                        $json[$key]['start_hour'] = "0" . $json[$key]['start_hour'];
                    }
                    if ($json[$key]['start_min'] < 10) {
                        $json[$key]['start_min'] = "0" . $json[$key]['start_min'];
                    }
                    if ($json[$key]['end_hour'] < 10) {
                        $json[$key]['end_hour'] = "0" . $json[$key]['end_hour'];
                    }
                    if ($json[$key]['end_min'] < 10) {
                        $json[$key]['end_min'] = "0" . $json[$key]['end_min'];
                    }
//                  是否营业
                    $time = date("Hi");
                    if ($value['start'] && $value['end']) {
                        if ($time >= $value['start'] && $time <= $value['end']) {
                            $json[$key]['is_open'] = true;
                        } else {
                            $json[$key]['is_open'] = false;
                        }
                        if (strlen($value['start']) == 3) {
                            $json[$key]['start_hour'] = '0'.substr($value['start'],0,1);
                            $json[$key]['start_min'] = substr($value['start'],2,2);
                        }elseif (strlen($value['start']) == 4){
                            $json[$key]['start_hour'] = substr($value['start'],0,2);
                            $json[$key]['start_min'] = substr($value['start'],2,2);
                        }
                        if (strlen($value['end']) == 3) {
                            $json[$key]['end_hour'] = '0'.substr($value['end'],0,1);
                            $json[$key]['end_min'] = substr($value['end'],2,2);
                        }elseif (strlen($value['end']) == 4){
                            $json[$key]['end_hour'] = substr($value['end'],0,2);
                            $json[$key]['end_min'] = substr($value['end'],2,2);
                        }
                    } else {
                        $json[$key]['is_open'] = true;
                    }
                    //相差多少英里
                    $json[$key]['mile'] = '2km';

                    $evaluation = DB::table('evaluation')->where(['bid' => $value['bid']])->field('avg(business_star) as avg_taste_star,avg(deliveryman_star) as avg_deliveryman_star,avg(service_star) as avg_service_star')->find();
                    $evaluation['avg_taste_star'] = round($evaluation['avg_taste_star']);
                    $evaluation['avg_deliveryman_star'] = round($evaluation['avg_deliveryman_star']);
                    $evaluation['avg_service_star'] = round($evaluation['avg_service_star']);
                    $evaluation['result_star'] = round(($evaluation['avg_taste_star'] + $evaluation['avg_deliveryman_star'] + $evaluation['avg_service_star']) / 3);
                    $json[$key]['evaluation'] = $evaluation;
                }
                $result['list'] = $json;
                $result['total'] = $total;
                return Response::create(['code' => 200,'status' => 1, 'msg' => '操作成功','data' => $result], 'json', 200);
        }
    }


    /**
     * 商家信息 + 得分评价
     */
    public function businessInfo(){
        switch ($this->method) {
            case 'get':
                $bid = input('get.bid');
                $json['business_info'] = DB::table('business_account')->where(['bid' => $bid])->field('name,phone,address,start_hour,start_min,end_hour,end_min,pic,cpc,bid,dp,tips,label,bg')->find();
                if ($json['business_info']['start_hour'] < 10) {
                    $json['business_info']['start_hour'] = "0" . $json['business_info']['start_hour'];
                }
                if ($json['business_info']['start_min'] < 10) {
                    $json['business_info']['start_min'] = "0" . $json['business_info']['start_min'];
                }
                if ($json['business_info']['end_hour'] < 10) {
                    $json['business_info']['end_hour'] = "0" . $json['business_info']['end_hour'];
                }
                if ($json['business_info']['end_min'] < 10) {
                    $json['business_info']['end_min'] = "0" . $json['business_info']['end_min'];
                }

                $evaluation = DB::table('evaluation')->where(['bid' => $bid])->field('avg(business_star) as avg_taste_star,avg(deliveryman_star) as avg_deliveryman_star,avg(service_star) as avg_service_star')->find();
                $evaluation['avg_taste_star'] = round($evaluation['avg_taste_star']);
                $evaluation['avg_deliveryman_star'] = round($evaluation['avg_deliveryman_star']);
                $evaluation['avg_service_star'] = round($evaluation['avg_service_star']);
                $evaluation['result_star'] = round(($evaluation['avg_taste_star'] + $evaluation['avg_deliveryman_star'] + $evaluation['avg_service_star']) / 3);
                $json['evaluation'] = $evaluation;
//                $goods = Db::table('business_to_goods');
//                $class = DB::table('business_to_goods_classifications');
//                $attribute = DB::table('business_to_goods_attributes');
//                $goods_list = $goods->where(['bid' => $bid])->field('name,price,pic,cid,gid,is_recommend')->select();
//                $classes = $class->where(['bid' => $bid])->field('name,cid')->order('weight desc')->select();
//                $json['goods'] = [];
//                $json['class_title'] = [];
//                $recommend = [];
//                //热门
//                $hot = $goods->where(['bid' => $bid])->field('name,price,pic,cid,gid,is_recommend')->order('sell_quantity desc,create_time desc')->limit(5)->select();
//                //热门商品属性赋值
//                foreach ($hot as &$h) {
//                    $h['attribute'] = $attribute->where(['gid' => $h['gid'], 'deleted' => 0])->select();
//                }
//                $json['goods']['0'] = $hot;
//                foreach ($classes as $k => $value) {
//                    $json['class_title'][$k]['title'] = $value['name'];
//                    $json['class_title'][$k]['id'] = $value['cid'];
//                }
//                array_unshift($json['class_title'], ['title' => '热门商品', 'id' => 0]);
//                foreach ($goods_list as $key => $value2) {
//                    $value2['attribute'] = $attribute->where(['gid' => $value2['gid'], 'deleted' => 0])->select();
//                    $json['goods'][$value2['cid']][] = $value2;
//                    //商家推荐
//                    if ($value2['is_recommend'] === 1) {
//                        $recommend[] = $value2;
//                    }
//                }
//                //是否允许商家推荐
//                if ($json['business_info']['recommend'] === 1) {
//                    $json['business_info']['recommend'] = $recommend;
//                } else {
//                    $json['business_info']['recommend'] = [];
//                }
//                $final_json['data'] = $json;
                return Response::create(['code' => 200,'status' => 1,'msg' => '操作成功','data' => $json], 'json', 200);
        }
    }


}