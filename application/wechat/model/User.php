<?php
namespace app\wechat\model;

use think\Model;

class User extends Model
{
    protected $pk = 'uid';
    protected $table = 'user';
    protected $autoWriteTimestamp = 'datetime';

    public function getInfoByOpenid($openid){
        return User::where(['openid' => $openid])->find();
    }

}