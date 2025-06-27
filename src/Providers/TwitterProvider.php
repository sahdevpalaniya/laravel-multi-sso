<?php

namespace Sahdev\SSO\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TwitterProvider
{
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $authUrl = 'https://twitter.com/i/oauth2/authorize';
    protected $tokenUrl = 'https://api.twitter.com/2/oauth2/token';
    protected $userInfoUrl = 'https://api.twitter.com/2/users/me';

    public function __construct()
    {
        $this->clientId = config('sso.twitter.client_id');
        $this->clientSecret = config('sso.twitter.client_secret');
        $this->redirectUri = config('sso.twitter.redirect');
    }

    protected function formatResponse($status, $statusCode, $message, $data = null)
    {
        return [
            'status' => $status,
            'status_code' => $statusCode,
            'message' => $message,
            'data' => $data,
        ];
    }

    public function redirect()
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'tweet.read users.read offline.access',
            'state' => csrf_token(),
            'code_challenge' => '', // Optional if PKCE is used
            'code_challenge_method' => '', // Optional
        ]);

        return redirect($this->authUrl . '?' . $query);
    }

    public function callback(Request $request)
    {
        try {
            $code = $request->get('code');

            if (!$code) {
                return $this->formatResponse(false, 400, 'Authorization code not found.');
            }

            $accessToken = $this->getAccessToken($code);
            $user = $this->getUserInfo($accessToken);

            $userDetails = [
                'id' => $user['id'] ?? null,
                'name' => $user['name'] ?? null,
                'email' => null, // Twitter API does not provide email unless elevated access is granted
                'avatar' => null, // Twitter API does not provide avatar directly via this endpoint
                'raw' => $user,
            ];

            return $this->formatResponse(true, 200, 'Twitter login successful.', $userDetails);
        } catch (\Exception $e) {
            return $this->formatResponse(false, 500, 'Exception occurred: ' . $e->getMessage());
        }
    }

    protected function getAccessToken($code)
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->post($this->tokenUrl, [
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUri,
                'client_id' => $this->clientId,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to retrieve access token: ' . $response->body());
        }

        return $response->json()['access_token'];
    }

    protected function getUserInfo($accessToken)
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get($this->userInfoUrl);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch user info: ' . $response->body());
        }

        return $response->json()['data'] ?? [];
    }
}