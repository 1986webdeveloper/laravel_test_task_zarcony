<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pizza_items extends Model
{
    protected $table = "pizza_items";

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Pizza_attribute>
     */
    public function attributes(){
        return $this->hasMany('App\Models\Pizza_attribute','pizza_id','id');
    }
}
