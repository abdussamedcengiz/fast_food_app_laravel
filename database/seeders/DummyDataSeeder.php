<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Customization;
use App\Models\MenuItem;

class DummyDataSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => "Burgers", 'description' => "Juicy grilled burgers"],
            ['name' => "Pizzas", 'description' => "Oven-baked cheesy pizzas"],
            ['name' => "Burritos", 'description' => "Rolled Mexican delights"],
            ['name' => "Sandwiches", 'description' => "Stacked and stuffed sandwiches"],
            ['name' => "Wraps", 'description' => "Rolled up wraps packed with flavor"],
            ['name' => "Bowls", 'description' => "Balanced rice and protein bowls"],
        ];
        foreach ($categories as $cat) Category::create($cat);

        $customizations = [
            ["name" => "Extra Cheese", "price" => 25, "type" => "topping"],
            ["name" => "Jalapeños", "price" => 20, "type" => "topping"],
            ["name" => "Onions", "price" => 10, "type" => "topping"],
            ["name" => "Olives", "price" => 15, "type" => "topping"],
            ["name" => "Mushrooms", "price" => 18, "type" => "topping"],
            ["name" => "Tomatoes", "price" => 10, "type" => "topping"],
            ["name" => "Bacon", "price" => 30, "type" => "topping"],
            ["name" => "Avocado", "price" => 35, "type" => "topping"],
            ["name" => "Coke", "price" => 30, "type" => "side"],
            ["name" => "Fries", "price" => 35, "type" => "side"],
            ["name" => "Garlic Bread", "price" => 40, "type" => "side"],
            ["name" => "Chicken Nuggets", "price" => 50, "type" => "side"],
            ["name" => "Iced Tea", "price" => 28, "type" => "side"],
            ["name" => "Salad", "price" => 33, "type" => "side"],
            ["name" => "Potato Wedges", "price" => 38, "type" => "side"],
            ["name" => "Mozzarella Sticks", "price" => 45, "type" => "side"],
            ["name" => "Sweet Corn", "price" => 25, "type" => "side"],
            ["name" => "Choco Lava Cake", "price" => 42, "type" => "side"],
        ];
        foreach ($customizations as $cus) Customization::create($cus);

        $menu = [
            [
                "name" => "Classic Cheeseburger",
                "description" => "Beef patty, cheese, lettuce, tomato",
                "image_url" => "https://static.vecteezy.com/system/resources/previews/044/844/600/large_2x/homemade-fresh-tasty-burger-with-meat-and-cheese-classic-cheese-burger-and-vegetable-ai-generated-free-png.png",
                "price" => 25.99,
                "rating" => 4.5,
                "calories" => 550,
                "protein" => 25,
                "category_name" => "Burgers",
                "customizations" => ["Extra Cheese", "Coke", "Fries", "Onions", "Bacon"],
            ],
            [
                "name" => "Pepperoni Pizza",
                "description" => "Loaded with cheese and pepperoni slices",
                "image_url" => "https://static.vecteezy.com/system/resources/previews/023/742/417/large_2x/pepperoni-pizza-isolated-illustration-ai-generative-free-png.png",
                "price" => 30.99,
                "rating" => 4.7,
                "calories" => 700,
                "protein" => 30,
                "category_name" => "Pizzas",
                "customizations" => ["Extra Cheese", "Jalapeños", "Garlic Bread", "Coke", "Olives"],
            ],
            [
                "name" => "Bean Burrito",
                "description" => "Stuffed with beans, rice, salsa",
                "image_url" => "https://static.vecteezy.com/system/resources/previews/055/133/581/large_2x/deliciously-grilled-burritos-filled-with-beans-corn-and-fresh-vegetables-served-with-lime-wedge-and-cilantro-isolated-on-transparent-background-free-png.png",
                "price" => 20.99,
                "rating" => 4.2,
                "calories" => 480,
                "protein" => 18,
                "category_name" => "Burritos",
                "customizations" => ["Jalapeños", "Iced Tea", "Fries", "Salad"],
            ],
            // ... diğer menüleri ekle (dummyData'dan kopyala)
        ];
        foreach ($menu as $item) {
            $cat = Category::where('name', $item['category_name'])->first();
            $menuItem = MenuItem::create([
                'name' => $item['name'],
                'description' => $item['description'],
                'image_url' => $item['image_url'],
                'price' => $item['price'],
                'rating' => $item['rating'],
                'calories' => $item['calories'],
                'protein' => $item['protein'],
                'category_id' => $cat->id,
            ]);
            $customizationIds = Customization::whereIn('name', $item['customizations'])->pluck('id')->toArray();
            $menuItem->customizations()->sync($customizationIds);
        }
    }
}


