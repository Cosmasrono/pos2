<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['name', 'code', 'address', 'phone', 'is_active', 'is_main', 'stock_distribution_percentage', 'owner_id'];

    protected $casts = [
        'stock_distribution_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'is_main' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function productStocks()
    {
        return $this->hasMany(\App\Models\ProductBranchStock::class);
    }
}