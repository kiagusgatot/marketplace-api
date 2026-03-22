<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'title',
        'description',
        'price',
        'thumbnail',
        'file_path',
    ];

    public function category() {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function seller() {
        return $this->belongsTo(User::class, 'seller_id');
    }
}