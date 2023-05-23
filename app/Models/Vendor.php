<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    //
    protected $table ='store';
    protected $guarded = []; 
    public function product(){
        return $this->belongsTo(Product::class, 'store_id'); 
        }
}
