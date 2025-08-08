<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customization;
use App\Models\MenuItem;

class MenuController extends Controller
{
    public function categories()
    {
        return Category::all();
    }

    public function customizations()
    {
        return Customization::all();
    }

    public function menuItems()
    {
        return MenuItem::with(['category', 'customizations'])->get();
    }
}
