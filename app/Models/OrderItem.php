<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class OrderItem extends Model
{
    //
    protected $table ='order_items';
    public function orders(){
        return $this->hasMany(Order::class, 'id');
    }
}
