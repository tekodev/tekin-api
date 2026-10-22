<?php

namespace App\Libraries;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Admin panel giris denemesi sinirlayici (brute-force korumasi).
 *
 * Uc ayri sayac tutulur, herhangi biri dolunca giris DB'ye hic gitmeden reddedilir:
 *  - kullanici adi + IP : tek saldirganin tek hesabi denemesi
 *  - IP                 : tek saldirganin bircok kullanici adini denemesi
 *  - kullanici adi      : bircok IP'den (botnet) tek hesabi denemesi
 *
 * IP, Cloudflare'in yazdigi CF-Connecting-IP'den alinir: X-Forwarded-For'un en soldaki
 * degeri istemci tarafindan sahtelenebilir (Cloudflare onu silmez, sonuna ekler) ve
 * TrustProxies '*' oldugu icin request()->ip() o sahte degeri dondururdu. Origin-lock
 * (DOCKER-USER sadece Cloudflare ipset'i) sayesinde her istek Cloudflare'den gelir.
 */
class AdminLoginThrottle
{
    private const LIMITS = [
        'user-ip' => ['max' => 5, 'decay' => 900],
        'ip' => ['max' => 20, 'decay' => 900],
        'user' => ['max' => 30, 'decay' => 3600],
    ];

    public function availableIn(Request $request): int
    {
        $wait = 0;

        foreach ($this->keys($request) as $scope => $key) {
            if (RateLimiter::tooManyAttempts($key, self::LIMITS[$scope]['max'])) {
                $wait = max($wait, RateLimiter::availableIn($key));
            }
        }

        if ($wait > 0) {
            Log::warning('Admin login throttled', [
                'ip' => $this->clientIp($request),
                'username' => Str::limit((string) $request->input('login_id'), 64),
                'retry_after' => $wait,
            ]);
        }

        return $wait;
    }

    public function recordFailure(Request $request): void
    {
        foreach ($this->keys($request) as $scope => $key) {
            RateLimiter::hit($key, self::LIMITS[$scope]['decay']);
        }
    }

    public function clear(Request $request): void
    {
        $keys = $this->keys($request);

        RateLimiter::clear($keys['user-ip']);
        RateLimiter::clear($keys['user']);
    }

    public function clientIp(Request $request): string
    {
        return (string) ($request->header('CF-Connecting-IP') ?: $request->ip());
    }

    private function keys(Request $request): array
    {
        $username = Str::lower(trim((string) $request->input('login_id')));
        $ip = $this->clientIp($request);

        return [
            'user-ip' => 'admin-login:user-ip:'.sha1($username.'|'.$ip),
            'ip' => 'admin-login:ip:'.sha1($ip),
            'user' => 'admin-login:user:'.sha1($username),
        ];
    }
}
