<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class DeviceIdentifierService
{
    public const COOKIE_NAME = 'qs_device_id';
    public const COOKIE_LIFETIME_DAYS = 365;

    public static function getOrCreate(Request $request): string
    {
        $deviceId = $request->cookie(self::COOKIE_NAME);
        
        if ($deviceId && self::isValidUuid($deviceId)) {
            return $deviceId;
        }

        return self::generateNew();
    }

    public static function generateNew(): string
    {
        return (string) Str::uuid();
    }

    public static function get(Request $request): ?string
    {
        $deviceId = $request->cookie(self::COOKIE_NAME);
        
        if ($deviceId && self::isValidUuid($deviceId)) {
            return $deviceId;
        }

        return null;
    }

    public static function createCookie(string $deviceId): Cookie
    {
        return cookie(
            self::COOKIE_NAME,                  // name
            $deviceId,                          // value
            self::COOKIE_LIFETIME_DAYS * 24 * 60, // minutes
            '/',                                // path
            null,                               // domain
            false,                              // secure (Force false for local HTTP)
            false,                              // httpOnly (False to allow JS access if needed)
            false,                              // raw
            'Lax'                               // sameSite
        );
    }

    public static function generate(Request $request): string
    {
        $existingId = self::get($request);
        if ($existingId) {
            return $existingId;
        }

        $ip = $request->ip() ?? 'unknown';
        $userAgent = $request->header('User-Agent') ?? 'unknown';
        
        $fingerprint = implode('|', [$ip, $userAgent, Str::random(16)]);
        
        return hash('sha256', $fingerprint);
    }

    public static function generateFromComponents(string $ip, string $userAgent): string
    {
        $fingerprint = implode('|', [$ip, $userAgent]);
        
        return hash('sha256', $fingerprint);
    }

    public static function matches(Request $request, string $storedDeviceId): bool
    {
        $currentDeviceId = self::get($request);
        return $currentDeviceId === $storedDeviceId;
    }

    private static function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }
}
