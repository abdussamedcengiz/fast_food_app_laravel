# Fast Food App — API

Sipariş uygulamasının REST API'si. Menü, kategoriler, özelleştirmeler ve
Sanctum token ile kullanıcı kimliği.

**Mobil istemci:** [fast_food_app](https://github.com/abdussamedcengiz/fast_food_app)
(React Native + Expo)

## Teknolojiler

- Laravel 8 + PHP 8
- Laravel Sanctum (API token kimlik doğrulama)
- Eloquent ORM, MySQL veya SQLite
- PHPUnit (14 test)

## Kurulum

```bash
composer install
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate
```

`.env` içinde veritabanını seç. En hızlısı SQLite:

```env
DB_CONNECTION=sqlite
# DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD satırlarını yorum yap
```

```bash
touch database/database.sqlite   # Windows: type nul > database\database.sqlite
php artisan migrate --seed
php artisan serve
```

API `http://127.0.0.1:8000` adresinde çalışır.
Kontrol: <http://127.0.0.1:8000/api/categories>

## API

Tüm uçlar `/api` önekiyle. Korumalı olanlar `Authorization: Bearer <token>` ister.

| Metot | Adres | Açıklama | Auth |
|---|---|---|---|
| POST | `/api/sign-up` | Kayıt ol, token döner (`201`) | – |
| POST | `/api/sign-in` | Giriş yap, token döner | – |
| POST | `/api/sign-out` | Token'ı iptal eder | ✔ |
| GET | `/api/user` | Oturumdaki kullanıcı | ✔ |
| GET | `/api/categories` | Kategoriler | – |
| GET | `/api/customizations` | Özelleştirmeler | – |
| GET | `/api/menu-items` | Menü — `?search=` ve `?category=` destekler | – |

### Menü filtreleri

```bash
# Ada veya açıklamaya göre ara
curl "http://127.0.0.1:8000/api/menu-items?search=pizza"

# Kategoriye göre filtrele
curl "http://127.0.0.1:8000/api/menu-items?category=1"

# İkisi birlikte + kayıt sınırı
curl "http://127.0.0.1:8000/api/menu-items?search=burger&category=1&limit=20"
```

### Doğrulama kuralları

| Alan | Kural |
|---|---|
| `name` | Zorunlu, en fazla 255 karakter |
| `email` | Geçerli e-posta; kırpılır ve **küçük harfe çevrilir** |
| `password` | Kayıtta en az 8 karakter |
| `search` | En fazla 100 karakter |
| `category` | Var olan bir kategori kimliği olmalı |
| `limit` | 1–100 |

Girişte şifre uzunluğu **kontrol edilmez**: kayıt kuralı sonradan 6'dan
8'e çıkarıldı ve eski kullanıcılar hesaplarına girebilmeli.

### Hata biçimi

Laravel standardı:

```json
{ "message": "The given data was invalid.", "errors": { "password": ["..."] } }
```

| Kod | Ne zaman |
|---|---|
| `401` | Token yok, geçersiz veya iptal edilmiş |
| `422` | Doğrulama hatası; yanlış e-posta/şifre |
| `429` | Hız sınırı aşıldı (kimlik uçlarında 6 istek/dk) |

## Güvenlik

- **Şifreler** bcrypt ile hash'lenir; `User` modelindeki `$hidden`
  sayesinde hiçbir cevapta yer almaz.
- **Kullanıcı sayımına karşı**, e-posta bulunamadığında da sahte bir
  hash ile karşılaştırma yapılır; cevap süresi her iki durumda aynıdır.
  Hata mesajı da birebir aynıdır.
- **E-posta normalizasyonu** doğrulamadan önce yapılır; böylece
  `unique:users` kuralı da normalize edilmiş değer üzerinden çalışır ve
  aynı adres iki farklı yazımla kaydedilemez.
- **Hız sınırı**: kimlik uçlarında 6 istek/dk (Laravel'in kendi giriş
  ekranlarında kullandığı sınır). Diğer uçlarda varsayılan 60/dk.
- **Çıkış** token'ı gerçekten siler. Sanctum token'ları varsayılan
  olarak süresizdir; çıkış ucu olmadan bir kez üretilen token sonsuza
  kadar geçerli kalırdı.
- **LIKE kaçışı**: aramada `%` ve `_` joker karakterleri kaçırılır,
  böylece kullanıcının yazdığı metin düz metin olarak aranır.

## Testler

```bash
./vendor/bin/phpunit
```

14 test, gerçek sorgularla çalışır (bellekte SQLite):

| Dosya | Kapsam |
|---|---|
| `tests/Feature/AuthTest.php` | Kayıt, şifre kuralı, e-posta normalizasyonu, tekrar kayıt, aynı hata mesajı, token iptali |
| `tests/Feature/MenuTest.php` | Menü listesi, arama, açıklamada arama, kategori filtresi, geçersiz kategori, ilişkiler |

Testler `phpunit.xml` içinde tanımlı **bellekteki** SQLite'ı kullanır.
Bu satırlar önceden yorum halindeydi; testler gerçek geliştirme
veritabanına bağlanıp `RefreshDatabase` ile onu silerdi.

## Proje yapısı

```
app/
├── Http/Controllers/
│   ├── AuthController.php    # Kayıt, giriş, çıkış
│   └── MenuController.php    # Menü, arama, filtre
└── Models/                   # User, Category, MenuItem, Customization
database/
├── migrations/
└── seeders/DummyDataSeeder.php
routes/api.php
tests/Feature/
```

## Bilinen eksikler

- **Sipariş yok.** Sepet yalnızca mobil tarafta tutuluyor; sipariş
  oluşturma ucu henüz yazılmadı.
- **Yetkilendirme yok.** Roller yok; menüyü değiştirecek bir yönetim
  ucu da yok (menü yalnızca seeder ile doluyor).

### ⚠️ Bağımlılıklarda bilinen güvenlik açıkları

Laravel 8'in güvenlik desteği **Ocak 2023'te bitti**. Bunun somut sonucu
ölçülebilir durumda:

```bash
composer audit
# Found 37 security vulnerability advisories affecting 11 packages.
```

Ayrıca modern Composer bu bağımlılık ağacını artık **çözemiyor bile**:
`composer update` çalıştırıldığında paketler güvenlik danışmanları
nedeniyle engelleniyor. Yani bağımlılıklar bugün olduğu gibi donmuş
durumda.

Ek olarak iki paket terk edilmiş:
- `fruitcake/laravel-cors` — Laravel 9'dan beri çekirdeğin parçası
- `facade/ignition` — yerini `spatie/laravel-ignition` aldı

**Bu bir yükseltme işidir**, düzeltme değil: Laravel 8 → 12 dört ana
sürüm demek ve `app/Http/Kernel.php` / `app/Exceptions/Handler.php`
gibi dosyaların tamamen kalktığı yeni iskelet yapısına geçmeyi
gerektirir. Bilinçli bir karar ve ayrı bir çalışma olarak planlanmalı.

## Lisans

MIT — ayrıntılar için [LICENSE](LICENSE) dosyasına bak.
