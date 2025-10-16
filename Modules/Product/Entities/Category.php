<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function products() {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }
    public function users(){
    return $this->belongsToMany(
        \App\Models\User::class,
        'user_categories',
        'category_id',
        'user_id'
    )->withPivot('user_id', 'category_id');
    }

}
