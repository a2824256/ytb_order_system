<?php
namespace app\business\model;

use think\Model;

class BusinessToGoods extends Model
{
    protected $pk = 'gid';
    protected $autoWriteTimestamp = 'datetime';
}