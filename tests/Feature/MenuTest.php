<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Category, 1: Category} */
    private function menuOlustur(): array
    {
        $burgerler = Category::create(['name' => 'Burgers', 'description' => 'Burger']);
        $pizzalar = Category::create(['name' => 'Pizzas', 'description' => 'Pizza']);

        MenuItem::create([
            'name' => 'Cheeseburger',
            'description' => 'Peynirli burger',
            // image_url migration'da NOT NULL: bos birakilirsa
            // kayit veritabani kisitina takilir.
            'image_url' => 'https://ornek.test/cheeseburger.png',
            'price' => 100,
            'rating' => 4.5,
            'calories' => 500,
            'protein' => 20,
            'category_id' => $burgerler->id,
        ]);

        MenuItem::create([
            'name' => 'Pepperoni Pizza',
            'description' => 'Baharatli pizza',
            'image_url' => 'https://ornek.test/pizza.png',
            'price' => 150,
            'rating' => 4.8,
            'calories' => 700,
            'protein' => 25,
            'category_id' => $pizzalar->id,
        ]);

        return [$burgerler, $pizzalar];
    }

    public function test_menu_listesi_doner(): void
    {
        $this->menuOlustur();

        $this->getJson('/api/menu-items')
            ->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_arama_gercekten_filtreler(): void
    {
        $this->menuOlustur();

        // ÖNCEDEN BU TEST GEÇMEZDİ: controller "search" parametresini
        // hiç okumuyordu ve her zaman tüm menüyü dönüyordu. Mobil
        // uygulamanın arama kutusu bu yüzden hiçbir şey filtrelemiyordu.
        $response = $this->getJson('/api/menu-items?search=pizza');

        $response->assertStatus(200)->assertJsonCount(1);
        $this->assertSame('Pepperoni Pizza', $response->json('0.name'));
    }

    public function test_arama_aciklamada_da_arar(): void
    {
        $this->menuOlustur();

        $this->getJson('/api/menu-items?search=Peynirli')
            ->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_kategoriye_gore_filtreler(): void
    {
        [$burgerler] = $this->menuOlustur();

        $response = $this->getJson('/api/menu-items?category=' . $burgerler->id);

        $response->assertStatus(200)->assertJsonCount(1);
        $this->assertSame('Cheeseburger', $response->json('0.name'));
    }

    public function test_olmayan_kategori_reddedilir(): void
    {
        $this->menuOlustur();

        $this->getJson('/api/menu-items?category=9999')
            ->assertStatus(422)
            ->assertJsonValidationErrors('category');
    }

    public function test_menu_iliskileri_ile_doner(): void
    {
        $this->menuOlustur();

        $this->getJson('/api/menu-items')
            ->assertStatus(200)
            ->assertJsonStructure([['id', 'name', 'price', 'category', 'customizations']]);
    }

    public function test_kategoriler_herkese_acik(): void
    {
        $this->menuOlustur();

        $this->getJson('/api/categories')->assertStatus(200)->assertJsonCount(2);
        $this->getJson('/api/customizations')->assertStatus(200);
    }
}
