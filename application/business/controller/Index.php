<?php
namespace app\business\controller;
use \think\View;
use \think\Session;
use \app\common\model\BusinessAccount;
class Index
{
    public function index()
    {
        $view = new View();
        return $view->fetch();
    }

    public function main()
    {
        $account = Session::get('business');
        $business = BusinessAccount::get(['account' => $account]);
        if (!empty($business)) {
            $business_info = $business->visible(['account','name','pic'])->toArray();
            $view = new View();
            $view->business_info = $business_info;
            return $view->fetch();
        }else{
            exit($account);
        }
    }
}
