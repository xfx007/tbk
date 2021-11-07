<?php
namespace app\index\model;

use think\Model;
use think\facade\Log;

class Order extends Model{
    protected $name = 'Order';

    protected $table = 'tb_order';

}