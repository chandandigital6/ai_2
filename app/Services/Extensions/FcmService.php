<?php

namespace App\Services\Extensions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FcmService
{
    public function enabled(): bool
    {
        return (bool) config('growya_extensions.fcm.enabled');
    }

    public function credentials(): array
    {
        $path = config('growya_extensions.fcm.credentials');
        if (! $path || ! is_file($path) || ! is_readable($path)) {
            throw new \RuntimeException('FCM credentials missing.');
        }
        $credentials = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($credentials) || (isset($credentials['type']) && $credentials['type'] !== 'service_account')) {
            throw new \RuntimeException('Use a Firebase service-account JSON, not google-services.json.');
        }
        foreach (['project_id', 'client_email', 'private_key'] as $key) {
            if (empty($credentials[$key])) {
                throw new \RuntimeException('FCM credentials incomplete.');
            }
        }

        return $credentials;
    }

    public function send(string $token, array $notification, array $data, string $platform = 'android'): array
    {
        abort_unless($this->enabled(), 503, 'FCM is disabled.');
        $credentials = $this->credentials();
        $access = Cache::remember('fcm-oauth:'.hash('sha256', $credentials['client_email'].$credentials['private_key']), 3000, function () use ($credentials) {
            $b64 = fn ($s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
            $head = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claim = $b64(json_encode(['iss' => $credentials['client_email'], 'scope' => 'https://www.googleapis.com/auth/firebase.messaging', 'aud' => 'https://oauth2.googleapis.com/token', 'iat' => time(), 'exp' => time() + 3600]));
            if (! openssl_sign($head.'.'.$claim, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new \RuntimeException('FCM signing failed.');
            }
            $r = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', ['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $head.'.'.$claim.'.'.$b64($signature)]);
            if (! $r->successful() || ! $r->json('access_token')) {
                throw new \RuntimeException('FCM authorization failed.');
            }

            return $r->json('access_token');
        });
        $message = ['token' => $token, 'data' => array_map('strval', array_merge($data, ['title' => $notification['title'] ?? '', 'body' => $notification['body'] ?? '']))];
        if ($platform === 'web') {
            $message['webpush'] = ['headers' => ['Urgency' => 'high', 'TTL' => '86400']];
        } else {
            $message['notification'] = $notification;
            if ($platform === 'android') {
                $message['android'] = ['priority' => 'high', 'notification' => ['sound' => 'default']];
            } elseif ($platform === 'ios') {
                $message['apns'] = ['payload' => ['aps' => ['sound' => 'default']]];
            }
        }
        $r = Http::withToken($access)->timeout(20)->post('https://fcm.googleapis.com/v1/projects/'.rawurlencode($credentials['project_id']).'/messages:send', ['message' => $message]);
        $code = collect($r->json('error.details', []))->pluck('errorCode')->filter()->first() ?: $r->json('error.status');
        if ($r->status() === 401) {
            Cache::forget('fcm-oauth:'.hash('sha256', $credentials['client_email'].$credentials['private_key']));
        }

        return ['ok' => $r->successful(), 'id' => $r->json('name'), 'code' => $code ?: 'HTTP_'.$r->status(), 'retry' => in_array($r->status(), [401, 429, 500, 502, 503, 504], true), 'invalid' => $code === 'UNREGISTERED'];
    }
}
