<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DamageProduct extends Model
{
    use HasFactory;

    protected $table = 'damage_products';

    protected $fillable = [
        'reference', 'date', 'product_id', 'qty', 'buy_price', 'sale_price', 'total', 'admin_id'
    ];

    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function admin(){
        return $this->belongsTo(AdminUser::class, 'admin_id');
    }
}
