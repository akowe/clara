<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'category';
    // protected $fillable = [
    //     'cat_id', 'cat_name', 'cat_description'
    // ];

// select all categories
 public function category()
    {
        return $this->hasOne(Category::class);
    }
     
}
