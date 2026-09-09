<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customization;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /** Menü listesinde dönülecek en fazla kayıt. */
    private const MAX_LIMIT = 100;

    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    public function customizations()
    {
        return Customization::orderBy('name')->get();
    }

    /**
     * Menü listesi. Arama ve kategori filtresi destekler.
     *
     * ÖNCEDEN FİLTRE YOKTU. Mobil uygulama arama kutusuna yazılanı
     * `?search=` olarak gönderiyordu (lib/api.ts -> searchMenuItems)
     * ama burada hiçbir yerde okunmuyordu: sunucu her seferinde tüm
     * menüyü dönüyordu. Yani arama çalışıyor gibi görünüyor, gerçekte
     * hiçbir şeyi filtrelemiyordu.
     */
    public function menuItems(Request $request)
    {
        $request->validate([
            'search'   => 'nullable|string|max:100',
            'category' => 'nullable|integer|exists:categories,id',
            'limit'    => 'nullable|integer|min:1|max:' . self::MAX_LIMIT,
        ]);

        $query = MenuItem::with(['category', 'customizations']);

        // when(): değer varsa koşulu ekler, yoksa sorguya hiç dokunmaz.
        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = (string) $request->input('search');

            // LIKE içinde % ve _ joker karakterlerdir. Kullanıcı "50%"
            // ararsa bunu joker değil düz metin olarak aramalıyız,
            // yoksa arama beklenmedik sonuçlar döner.
            //
            // KAÇIŞ KARAKTERİ NEDEN "!" ve NEDEN ESCAPE CÜMLESİ ŞART?
            //
            // Kaçırmanın işe yaraması için LIKE'a kaçış karakterinin
            // ne olduğunun SÖYLENMESİ gerekir. MySQL ESCAPE cümlesi
            // yoksa varsayılan olarak ters eğik çizgiyi kabul eder,
            // SQLite ise HİÇBİR varsayılan tanımaz. Bu proje testlerde
            // ve yerel geliştirmede SQLite kullandığı için, ESCAPE
            // yazılmadığında "50%" aramasi hiçbir sonuç dönmüyordu.
            //
            // Ters eğik çizgi de kullanılamaz: MySQL onu metin
            // sabitlerinin içinde kendisi de kaçış karakteri saydığı
            // için ESCAPE '' iki motorda farklı okunur. "!" ise her
            // iki motorda da sıradan bir karakter, tek yazımla ikisinde
            // de doğru çalışıyor.
            //
            // Önce "!" ikileniyor: kullanıcı gerçekten "!" ararsa bu
            // karakterin kaçış işareti sanılmasını engelliyor.
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search);

            // Gruplama şart: parantezsiz yazılsaydı orWhere, dışarıdaki
            // kategori koşulunu da geçersiz kılardı
            // (A AND B OR C yerine A AND (B OR C) istiyoruz).
            //
            // ESCAPE değeri sabit bir harf; kullanıcı girdisi yalnızca
            // bağlanan parametrede (?) taşınıyor.
            $q->where(function ($sub) use ($escaped) {
                $sub->whereRaw("name LIKE ? ESCAPE '!'", ['%' . $escaped . '%'])
                    ->orWhereRaw("description LIKE ? ESCAPE '!'", ['%' . $escaped . '%']);
            });
        });

        $query->when($request->filled('category'), function ($q) use ($request) {
            $q->where('category_id', (int) $request->input('category'));
        });

        // Üst sınır: menü büyüdükçe cevabın sınırsız büyümesini engeller.
        $limit = (int) $request->input('limit', self::MAX_LIMIT);

        return $query->orderBy('name')->limit($limit)->get();
    }
}
