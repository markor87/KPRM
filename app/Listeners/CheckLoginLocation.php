<?php

namespace App\Listeners;

use App\Mail\SuspiciousLoginMail;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;

class CheckLoginLocation
{
    private const ALLOWED_COUNTRY = 'RS';

    public function handle(Login $event): void
    {
        $this->checkLocation(
            userName: $event->user->name,
            userEmail: $event->user->email,
            successful: true,
        );
    }

    /**
     * Rano upozorenje: kredencijali su tacni, ali korisnik jos nije zavrsio 2FA.
     * Znaci da neko sa ispravnom lozinkom pristupa iz inostranstva - okida se pre
     * nego sto se 2FA zavrsi (ili cak i ako se ne zavrsi). Poziva se iz Login stranice.
     */
    public function handleValidCredentials(User $user): void
    {
        $this->checkLocation(
            userName: $user->name,
            userEmail: $user->email,
            successful: true,
        );
    }

    private function checkLocation(string|null $userName, string $userEmail, bool $successful): void
    {
        $ip = Request::ip();

        if ($this->isPrivateIp($ip)) {
            return;
        }

        $adminEmail = config('app.admin_email');
        if (empty($adminEmail)) {
            return;
        }

        // Dedup: isti korisnik sa iste IP adrese ne dobija dva mejla za istu prijavu
        // (npr. rano upozorenje na 2FA koraku + uspesna prijava nakon unosa koda).
        // Kljuc se postavlja tek nakon uspesno poslatog mejla, pa privremeni pad
        // ip-api-ja ne blokira kasniji pokusaj slanja.
        $dedupKey = 'geo-login-alert:' . md5($ip . '|' . $userEmail);
        if (Cache::has($dedupKey)) {
            return;
        }

        try {
            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,country,countryCode,city,query',
            ]);

            if (! $response->successful()) {
                return;
            }

            $data = $response->json();

            if (($data['status'] ?? '') !== 'success') {
                return;
            }

            if (($data['countryCode'] ?? '') !== self::ALLOWED_COUNTRY) {
                Mail::to($adminEmail)->send(new SuspiciousLoginMail(
                    userName: $userName ?? 'Непознато',
                    userEmail: $userEmail,
                    ip: $ip,
                    country: $data['country'] ?? 'Непознато',
                    city: $data['city'] ?? 'Непознато',
                    successful: $successful,
                ));

                Cache::put($dedupKey, true, now()->addMinutes(10));
            }
        } catch (\Throwable $e) {
            Log::warning('Geo provera prijave nije uspela', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isPrivateIp(string $ip): bool
    {
        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return true;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
