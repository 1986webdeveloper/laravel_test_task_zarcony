<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order_master extends Model
{
    use SoftDeletes;

    protected $table = "order_master";
    protected $dates = ['deleted_at'];

    public $fillable = ['customer_name', 'customer_address', 'customer_mobile', 'total_quantity', 'total_amount', 'order_status'];

    public function order_items(){
        return $this->hasMany('App\Order_items','order_id','id');
    }
}
