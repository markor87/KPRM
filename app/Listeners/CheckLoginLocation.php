<?php

namespace App\Listeners;

use App\Mail\SuspiciousLoginMail;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;

class CheckLoginLocation
{
    private const ALLOWED_COUNTRY = 'RS';

    public function handle(Login $event): void
    {
        $ip = Request::ip();

        // Skip local/private IPs (dev environment, internal network)
        if ($this->isPrivateIp($ip)) {
            return;
        }

        $adminEmail = config('app.admin_email');
        if (empty($adminEmail)) {
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
                    user: $event->user,
                    ip: $ip,
                    country: $data['country'] ?? 'Непознато',
                    city: $data['city'] ?? 'Непознато',
                ));
            }
        } catch (\Throwable $e) {
            // Fail silently — geo check must never block a login
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
