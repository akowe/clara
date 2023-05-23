<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Order extends Model
{
    //
    protected $table ='order';
    public function OrderItem(){
    return $this->belongsTo(OrderItem::class, 'order_id'); 
    }
}
