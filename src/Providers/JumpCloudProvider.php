<?php

namespace Sahdev\SSO\Providers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Sahdev\SSO\Contracts\SSOProviderInterface;

class JumpCloudProvider implements SSOProviderInterface
{
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $authUrl = 'https://oauth.id.jumpcloud.com/oauth2/auth';
    protected $tokenUrl = 'https://oauth.id.jumpcloud.com/oauth2/token';
    protected $userInfoUrl = 'https://oauth.id.jumpcloud.com/userinfo';

    public function __construct()
    {
        $this->clientId = config('sso.jumpcloud.client_id');
        $this->clientSecret = config('sso.jumpcloud.client_secret');
        $this->redirectUri = config('sso.jumpcloud.redirect');
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
        $state = Str::random(40);
        session(['jumpcloud_oauth_state' => $state]);

        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
        ]);

        return redirect($this->authUrl . '?' . $query);
    }

    public function callback(Request $request)
    {
        $state = session('jumpcloud_oauth_state');

        if (!$request->has('state') || $request->state !== $state) {
            return $this->formatResponse(false, 400, 'Invalid state parameter');
        }

        if (!$request->has('code')) {
            return $this->formatResponse(false, 400, 'Authorization code not provided');
        }

        try {
            $accessToken = $this->getAccessToken($request->code);
            $userData = $this->getUserByToken($accessToken);

            $userDetails = [
                'id' => $userData['sub'] ?? null,
                'name' => $userData['name'] ?? null,
                'email' => $userData['email'] ?? null,
                'given_name' => $userData['given_name'] ?? null,
                'family_name' => $userData['family_name'] ?? null,
                'email_verified' => $userData['email_verified'] ?? false,
                'accessToken' => $accessToken,
            ];

            return $this->formatResponse(true, 200, 'JumpCloud login successful.', $userDetails);
        } catch (\Exception $e) {
            return $this->formatResponse(false, 500, 'Exception occurred: ' . $e->getMessage());
        }
    }

    protected function getAccessToken($code)
    {
        $response = Http::asForm()->post($this->tokenUrl, [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to get access token: ' . $response->body());
        }

        return $response->json('access_token');
    }

    protected function getUserByToken($token)
    {
        $response = Http::withToken($token)->get($this->userInfoUrl);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch user information: ' . $response->body());
        }

        return $response->json();
    }
}