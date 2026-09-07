<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Kimlik doğrulama uçlarının testleri.
 *
 * Düzeltilen davranışları kalıcı olarak koruyorlar: şifre kuralı,
 * e-posta normalizasyonu, kullanıcı sayımına karşı aynı hata mesajı
 * ve token iptali.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_kayit_olur_ve_token_doner(): void
    {
        $response = $this->postJson('/api/sign-up', [
            'name' => 'Test Kullanici',
            'email' => 'yeni@ornek.com',
            'password' => 'gecerli-sifre',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        // Şifre hash'i cevaba ASLA girmemeli.
        $this->assertArrayNotHasKey('password', $response->json('user'));

        $this->assertDatabaseHas('users', ['email' => 'yeni@ornek.com']);
    }

    public function test_kisa_sifreyi_reddeder(): void
    {
        // Kural 6'dan 8'e çıkarıldı; bu test onu sabitliyor.
        $this->postJson('/api/sign-up', [
            'name' => 'Test',
            'email' => 'kisa@ornek.com',
            'password' => '123456',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_epostayi_kucuk_harfe_cevirir(): void
    {
        $this->postJson('/api/sign-up', [
            'name' => 'Test',
            'email' => '  Buyuk@Ornek.COM  ',
            'password' => 'gecerli-sifre',
        ])->assertStatus(201);

        // Kayıt normalize edilmiş halde saklanmalı.
        $this->assertDatabaseHas('users', ['email' => 'buyuk@ornek.com']);

        // Ve büyük harfle giriş yapılabilmeli.
        $this->postJson('/api/sign-in', [
            'email' => 'BUYUK@ORNEK.COM',
            'password' => 'gecerli-sifre',
        ])->assertStatus(200)->assertJsonStructure(['token']);
    }

    public function test_ayni_eposta_ikinci_kez_kaydedilemez(): void
    {
        User::factory()->create(['email' => 'var@ornek.com']);

        $this->postJson('/api/sign-up', [
            'name' => 'Test',
            'email' => 'var@ornek.com',
            'password' => 'gecerli-sifre',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_yanlis_sifre_ve_olmayan_kullanici_ayni_cevabi_alir(): void
    {
        User::factory()->create([
            'email' => 'kayitli@ornek.com',
            'password' => Hash::make('dogru-sifre'),
        ]);

        $yanlisSifre = $this->postJson('/api/sign-in', [
            'email' => 'kayitli@ornek.com',
            'password' => 'yanlis-sifre',
        ]);

        $olmayanKullanici = $this->postJson('/api/sign-in', [
            'email' => 'yok@ornek.com',
            'password' => 'herhangi',
        ]);

        $yanlisSifre->assertStatus(422);
        $olmayanKullanici->assertStatus(422);

        // Mesajlar BİREBİR aynı olmalı: farklı olsaydı saldırgan hangi
        // e-postaların kayıtlı olduğunu öğrenebilirdi.
        $this->assertSame(
            $yanlisSifre->json('errors.email'),
            $olmayanKullanici->json('errors.email')
        );
    }

    public function test_cikis_token_i_iptal_eder(): void
    {
        $user = User::factory()->create(['password' => Hash::make('gecerli-sifre')]);

        $token = $this->postJson('/api/sign-in', [
            'email' => $user->email,
            'password' => 'gecerli-sifre',
        ])->json('token');

        // Token önce geçerli.
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user')->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/sign-out')->assertStatus(200);

        // TOKEN GERÇEKTEN SİLİNDİ Mİ?
        //
        // Burada "aynı token ile tekrar istek at, 401 bekle" demiyoruz.
        // Sebep bir test artefaktı: tek bir test metodu içindeki
        // istekler AYNI uygulama örneğini paylaşır ve Sanctum guard'ı
        // çözdüğü kullanıcıyı önbelleğe alır -- silinmiş bir token'la
        // bile ikinci istek 200 döner. Gerçek HTTP'de böyle olmaz.
        //
        // Kaydın silindiğini doğrulamak hem daha doğrudan hem de bu
        // artefakttan bağımsız.
        //
        // Önceden çıkış endpoint'i hiç yoktu: bir kez üretilen token
        // sonsuza kadar geçerli kalıyordu.
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_korumali_uc_tokensiz_erisimi_reddeder(): void
    {
        $this->getJson('/api/user')->assertStatus(401);
    }
}
