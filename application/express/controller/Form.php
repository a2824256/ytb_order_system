<?php
namespace app\express\controller;

use \app\express\model\BusinessToOrders;
use \think\Controller;
use think\Db;
use \think\Session;
use \app\express\model\Deliveryman;
class Form extends Controller
{

    public function login(){
        $acc = input('post.acc');
        $pwd = input('post.pwd');
        if(!empty($acc)||!empty($pwd)){
            $res = Deliveryman::where(["acc"=>$acc,"pwd"=>$pwd])->field('did,name,telephone,openid')->find();
            if($res) {
                Session::set('acc', $acc);
                Session::set('deliveryman', $res);
                $this->success("登录成功!","index/main");
            }else{
                $this->error("账号或密码错误!".$acc."~".$pwd);
            }
        }else{
            $this->error("账号或密码为空!");
        }
    }

    /**
     * 流程代码修改
    */
    public function updateOrder(){
        $id = input('post.id');
        $step = intval(input('post.step'));
        if(empty($id)){
            return json(['errcode' => -1,'errmsg' => '操作失败1']);
        }
        if(!in_array($step,[1,3,4])){
            return json(['errcode' => -2,'errmsg' => '参数错误']);
        }
        $order = Db::table('order')->where(['oid' => $id])->find();
        if($order['step'] > $step){
            return json(['errcode' => -3,'errmsg' => '其他骑手已操作，无须重复操作,请刷新页面查看']);
        }

        if($step === 1){
            //骑手已取餐
            $data = 3;
            $insertData = [
                'order_number' => $order['order_number'],
                'did' => Session::get('deliveryman')->did,
                'create_time' => date('Y-m-d H:i:s'),
                'type' => 'receive',
            ];
        }
        if($step === 3){
            //骑手已送达
            $data = 4;
            $insertData = [
                'order_number' => $order['order_number'],
                'did' => Session::get('deliveryman')->did,
                'create_time' => date('Y-m-d H:i:s'),
                'type' => 'service',
            ];
        }

        if(Db::table('order')->where(['oid' => $id])->setField('step',$data)){
            Db::table('order_deliveryman')->insert($insertData);
            return json(['errcode' => 0, 'errmsg' => 'ok','data' => $data]);
        }else{
            return json(['errcode' => -1,'errmsg' => '操作失败2']);
        }
    }

}