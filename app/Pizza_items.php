<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Pizza_items extends Model
{
    public function attributes(){
        return $this->hasMany('App\Pizza_attribute','pizza_id','id');
    }
}
