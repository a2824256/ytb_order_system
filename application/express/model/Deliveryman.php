<?php
namespace app\express\model;

use think\Model;

class Deliveryman extends Model
{
    protected $pk = 'did';
    protected $autoWriteTimestamp = 'datetime';
}