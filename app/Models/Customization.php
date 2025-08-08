<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customization extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'price', 'type'];
    public function menuItems() { return $this->belongsToMany(MenuItem::class, 'menu_item_customization'); }
}
