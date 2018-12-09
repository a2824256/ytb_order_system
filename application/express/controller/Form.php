<?php
namespace app\express\controller;

use \think\Controller;
use \think\Session;
use \app\express\model\Deliveryman;
use \app\business\model\BusinessToOrders;
class Form extends Controller
{
    public function login(){
        $acc = input('post.acc');
        $pwd = input('post.pwd');
        if(!empty($acc)||!empty($pwd)){
            $res = Deliveryman::where(["acc"=>$acc,"pwd"=>$pwd])->find();
            if($res) {
                Session::set('acc', $acc);
                $this->success("登录成功!","index/main");
            }else{
                $this->error("账号或密码错误!".$acc."~".$pwd);
            }
        }else{
            $this->error("账号或密码为空!");
        }
    }

    public function updateOrder(){
        $id = input('get.id');
        if(empty($id)){
            $this->error("错误操作!");
        }
        $res = BusinessToOrders::where(["oid"=>$id])->update(['status'=>2]);
        if($res){
            $this->success("操作成功!");
        }else{
            $this->success("操作失败!");
        }
    }
}