<?php

namespace App\Services;

use App\Contracts\SocialMediaProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookService implements SocialMediaProvider
{
    protected string $baseUrl = 'https://graph.facebook.com/v18.0';

    public function getAuthUrl(string $clientId = null): string
    {
        $clientId = $clientId ?? config('services.facebook.client_id');
        return "https://www.facebook.com/v20.0/dialog/oauth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => config('services.facebook.redirect_uri'),
            'scope' => 'pages_manage_posts,pages_read_engagement,publish_video,pages_show_list',
            'state' => csrf_token(),
        ]);
    }

    public function getAccessToken(string $code, string $clientId = null, string $clientSecret = null): array
    {
        $clientId = $clientId ?? config('services.facebook.client_id');
        $clientSecret = $clientSecret ?? config('services.facebook.client_secret');

        $response = Http::get("{$this->baseUrl}/oauth/access_token", [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => config('services.facebook.redirect_uri'),
            'code' => $code,
        ]);

        if ($response->failed()) {
            Log::error('FB token failed', $response->json());
            throw new \Exception('فشل الحصول على Token');
        }

        return $response->json();
    }

    public function getLongLivedToken(string $shortToken, string $clientId = null, string $clientSecret = null): array
    {
        $clientId = $clientId ?? config('services.facebook.client_id');
        $clientSecret = $clientSecret ?? config('services.facebook.client_secret');

        $response = Http::get("{$this->baseUrl}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'fb_exchange_token' => $shortToken,
        ]);

        if ($response->failed()) {
            Log::error('Long-lived token failed', $response->json());
            throw new \Exception('فشل تحويل Token');
        }

        $data = $response->json();
        
        return [
            'access_token' => $data['access_token'],
            'expires_in' => $data['expires_in'] ?? 5184000,
            'expires_at' => now()->addSeconds($data['expires_in'] ?? 5184000),
        ];
    }

    public function getUserPages(string $userToken): array
    {
        $response = Http::get("{$this->baseUrl}/me/accounts", [
            'access_token' => $userToken,
            'fields' => 'id,name,access_token,category',
        ]);

        if ($response->failed()) {
            Log::error('Get pages failed', $response->json());
            return [];
        }

        return $response->json('data') ?? [];
    }

    public function getPageToken(string $userToken, string $pageId): ?string
    {
        $response = Http::get("{$this->baseUrl}/{$pageId}", [
            'fields' => 'access_token',
            'access_token' => $userToken,
        ]);

        if ($response->failed()) {
            Log::error("Page token failed for {$pageId}", $response->json());
            return null;
        }

        return $response->json('access_token');
    }

    public function post(string $token, string $pageId, array $data): string
    {
        $endpoint = "{$this->baseUrl}/{$pageId}/feed";
        
        $payload = [
            'message' => $data['content'] ?? '',
            'access_token' => $token,
        ];

        if (!empty($data['media_url'])) {
            if (($data['media_type'] ?? '') === 'image') {
                $endpoint = "{$this->baseUrl}/{$pageId}/photos";
                $payload['url'] = $data['media_url'];
            } elseif (($data['media_type'] ?? '') === 'video') {
                $endpoint = "{$this->baseUrl}/{$pageId}/videos";
                $payload['file_url'] = $data['media_url'];
            }
        }

        $response = Http::timeout(30)->post($endpoint, $payload);

        if ($response->failed()) {
            $error = $response->json();
            $errorMsg = $error['error']['message'] ?? 'Unknown Error';
            
            Log::error('Post failed', [
                'error' => $errorMsg,
                'page_id' => $pageId,
            ]);

            throw new \Exception("Facebook Error: {$errorMsg}");
        }

        return (string) ($response->json('id') ?? $response->json('post_id'));
    }

    public function validateToken(string $token): bool
    {
        $response = Http::get("{$this->baseUrl}/me", [
            'access_token' => $token,
        ]);

        return $response->successful();
    }

    public function debugToken(string $token): ?array
    {
        $appToken = config('services.facebook.client_id') . '|' . config('services.facebook.client_secret');

        $response = Http::get("{$this->baseUrl}/debug_token", [
            'input_token' => $token,
            'access_token' => $appToken,
        ]);

        return $response->successful() ? $response->json('data') : null;
    }
}