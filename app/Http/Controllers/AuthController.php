<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function signUp(Request $request)
    {
        // E-POSTA ÖNCE NORMALİZE, SONRA DOĞRULANIYOR.
        //
        // "Ali@X.com" ile "ali@x.com" aynı hesaptır. Normalize
        // etmezsek kullanıcı kayıt olurken kullandığı büyük/küçük harf
        // düzenini hatırlamak zorunda kalır -- hatırlamazsa giriş
        // yapamaz. Ayrıca "unique:users" kuralı da normalize edilmiş
        // değer üzerinden çalışsın istiyoruz, yoksa aynı adres iki
        // farklı yazımla iki kez kaydedilebilirdi.
        $this->normalizeEmail($request);

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            // 6 değil 8: 6 karakterlik bir şifre günümüzde kaba
            // kuvvetle makul sürede kırılabilir.
            'password' => 'required|string|min:8|max:255',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        // 201 = yeni kaynak oluşturuldu. Önceden 200 dönüyordu.
        return response()->json(['token' => $token, 'user' => $user], 201);
    }

    public function signIn(Request $request)
    {
        $this->normalizeEmail($request);

        $data = $request->validate([
            'email'    => 'required|email',
            // Girişte uzunluk kuralı UYGULANMIYOR: kayıt kuralları
            // sonradan sıkılaştığında (6 -> 8) eski kullanıcılar kendi
            // hesaplarına girebilmeli.
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        // ZAMANLAMA SALDIRISINA KARŞI.
        //
        // Önceden kullanıcı bulunamazsa Hash::check hiç çalışmıyordu:
        // var olmayan bir e-posta ~1ms'te, kayıtlı olan ~100ms'te cevap
        // dönüyordu. Saldırgan cevabın içeriğine değil SÜRESİNE bakarak
        // hangi e-postaların kayıtlı olduğunu öğrenebilirdi.
        //
        // Kullanıcı yoksa da aynı maliyetli işlemi yapıyoruz.
        $hashedPassword = $user->password ?? Hash::make('zamanlama-saldirisina-karsi');
        $passwordMatches = Hash::check($data['password'], $hashedPassword);

        if (! $user || ! $passwordMatches) {
            // ValidationException: Laravel'in standart 422 hata biçimi.
            // Hangi alanın yanlış olduğunu SÖYLEMİYORUZ.
            throw ValidationException::withMessages([
                'email' => ['E-posta veya şifre hatalı.'],
            ]);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    /**
     * İstekteki e-postayı kırpıp küçük harfe çevirir.
     * Doğrulamadan ÖNCE çağrılmalı.
     */
    private function normalizeEmail(Request $request): void
    {
        if ($request->has('email') && is_string($request->input('email'))) {
            $request->merge([
                'email' => mb_strtolower(trim($request->input('email'))),
            ]);
        }
    }

    /**
     * Oturumu kapatır.
     *
     * ÖNCEDEN BÖYLE BİR ENDPOINT YOKTU. Sanctum token'ları varsayılan
     * olarak SÜRESİZDİR; çıkış yapmanın bir yolu olmadığı için bir kez
     * üretilen token sonsuza kadar geçerli kalıyordu. Telefonu
     * kaybeden bir kullanıcının erişimi iptal etmesi mümkün değildi.
     */
    public function signOut(Request $request)
    {
        // Yalnızca bu isteği yapan token siliniyor; kullanıcının diğer
        // cihazlardaki oturumları etkilenmiyor.
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Çıkış yapıldı.']);
    }
}
