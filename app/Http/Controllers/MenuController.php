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
            $escaped = addcslashes($search, '%_');

            // Gruplama şart: parantezsiz yazılsaydı orWhere, dışarıdaki
            // kategori koşulunu da geçersiz kılardı
            // (A AND B OR C yerine A AND (B OR C) istiyoruz).
            $q->where(function ($sub) use ($escaped) {
                $sub->where('name', 'like', '%' . $escaped . '%')
                    ->orWhere('description', 'like', '%' . $escaped . '%');
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
