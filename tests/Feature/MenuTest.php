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

    /** Adı verilen tek bir menü ürünü oluşturur. */
    private function urunEkle(string $ad): void
    {
        $kategori = Category::firstOrCreate(
            ['name' => 'Test'],
            ['description' => 'Test kategorisi']
        );

        MenuItem::create([
            'name' => $ad,
            'description' => 'aciklama',
            'image_url' => 'https://ornek.test/urun.png',
            'price' => 10,
            'rating' => 4,
            'calories' => 100,
            'protein' => 5,
            'category_id' => $kategori->id,
        ]);
    }

    /**
     * REGRESYON: "%" düz metin olarak aranmalı.
     *
     * Arama LIKE ile yapılıyor ve "%" LIKE içinde jokerdir. Kaçırma
     * yapılıyordu ama sorguda ESCAPE cümlesi yoktu; SQLite hiçbir
     * varsayılan kaçış karakteri tanımadığı için "50%" araması
     * hiçbir sonuç dönmüyordu.
     */
    public function test_yuzde_isareti_duz_metin_olarak_aranir(): void
    {
        $this->urunEkle('50% Indirimli Menu');
        $this->urunEkle('Normal Burger');

        $adlar = array_column(
            $this->getJson('/api/menu-items?search=' . urlencode('50%'))
                ->assertStatus(200)
                ->json(),
            'name'
        );

        $this->assertContains('50% Indirimli Menu', $adlar);
        $this->assertNotContains('Normal Burger', $adlar);
    }

    /**
     * REGRESYON: "_" de jokerdir (tek karakter eşler).
     *
     * Kaçırma çalışmazsa "Combo_1" araması "ComboX1" kaydını da
     * getirir; ESCAPE eksikken ise hiçbir şey getirmiyordu.
     */
    public function test_alt_cizgi_joker_gibi_davranmaz(): void
    {
        $this->urunEkle('Combo_1');
        $this->urunEkle('ComboX1');

        $adlar = array_column(
            $this->getJson('/api/menu-items?search=' . urlencode('Combo_1'))
                ->assertStatus(200)
                ->json(),
            'name'
        );

        $this->assertContains('Combo_1', $adlar);
        $this->assertNotContains('ComboX1', $adlar);
    }

    /**
     * REGRESYON: kaçış karakterinin kendisi de aranabilmeli.
     *
     * "!" kaçış karakteri olarak seçildiği için, kullanıcı gerçekten
     * "!" ararsa bunun joker işareti sanılmaması gerekiyor.
     */
    public function test_unlem_isareti_aranabilir(): void
    {
        $this->urunEkle('Acili Burger!');
        $this->urunEkle('Sade Burger');

        $adlar = array_column(
            $this->getJson('/api/menu-items?search=' . urlencode('Burger!'))
                ->assertStatus(200)
                ->json(),
            'name'
        );

        $this->assertContains('Acili Burger!', $adlar);
        $this->assertNotContains('Sade Burger', $adlar);
    }
}
