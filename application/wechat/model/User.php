<?php
namespace app\wechat\model;

use think\Model;

class User extends Model
{
    protected $pk = 'uid';
    protected $table = 'user';
    protected $autoWriteTimestamp = 'datetime';

    public function getInfoByOpenid($uid){
        return User::where(['uid' => $uid])->find();
    }

}