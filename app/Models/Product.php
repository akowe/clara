<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    use HasFactory;
    protected $table ='product';
    protected $guarded = [];
    //protected $casts = [ 'size' =>'array' ];
    public function store(){
        return $this->hasMany(Store::class, 'id');
    }
}
