<?php

namespace Sahdev\SSO\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoogleProvider
{
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;

    public function __construct()
    {
        $this->clientId = config('sso.google.client_id');
        $this->clientSecret = config('sso.google.client_secret');
        $this->redirectUri = config('sso.google.redirect');
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
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function callback(Request $request)
    {
        try {
            $code = $request->get('code');
            if (!$code) {
                return $this->formatResponse(false, 400, 'Authorization code not found.');
            }

            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);

            if (!$tokenResponse->successful()) {
                return $this->formatResponse(
                    false,
                    $tokenResponse->status(),
                    'Failed to get access token.',
                    $tokenResponse->json()
                );
            }

            $accessToken = $tokenResponse->json()['access_token'];

            $userResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if (!$userResponse->successful()) {
                return $this->formatResponse(
                    false,
                    $userResponse->status(),
                    'Failed to fetch user info.',
                    $userResponse->json()
                );
            }

            $user = $userResponse->json();

            return $this->formatResponse(
                true,
                200,
                'Google login successful.',
                [
                    'sub' => $user['sub'],
                    'name' => $user['name'],
                    'given_name' => $user['given_name'],
                    'family_name' => $user['family_name'],
                    'picture' => $user['picture'],
                    'email' => $user['email'],
                    'email_verified' => $user['email_verified'],
                ]
            );
        } catch (\Exception $e) {
            return $this->formatResponse(false, 500, 'Exception occurred: ' . $e->getMessage());
        }
    }
}