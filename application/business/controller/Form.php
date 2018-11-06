<?php
/**
 * Created by PhpStorm.
 * User: AlexLeung
 * Date: 2018/11/5
 * Time: 7:15
 */

namespace app\business\controller;

use \think\Controller;
use \app\business\model\BusinessToGoods;
use \app\business\model\BusinessToGoodsClassifications;
use \app\common\model\BusinessAccount;
use \think\Session;

class Form extends Controller
{
    public function deleteGood()
    {
        $account = Session::get('business');
        if (!empty($account)) {
            $gid = input('get.gid');
            $res = BusinessToGoods::where(['gid' => $gid])->delete();
            if ($res) {
                $this->success('Delete success.', 'index/goods');
            } else {
                $this->error('Delete fail.');
            }
        } else {
            $this->error('Please login!', 'index/index');
        }
    }

    public function deleteClassifications()
    {
        $account = Session::get('business');
        if (!empty($account)) {
            $cid = input('get.cid');
            $res = BusinessToGoodsClassifications::where(['cid' => $cid])->delete();
            if ($res) {
                $this->success('Delete success.', 'index/classifications');
            } else {
                $this->error('Delete fail.');
            }
        } else {
            $this->error('Please login!', 'index/index');
        }
    }

}