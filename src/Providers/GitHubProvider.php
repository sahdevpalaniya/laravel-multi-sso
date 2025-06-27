<?php

namespace Sahdev\SSO\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GitHubProvider
{
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;

    public function __construct()
    {
        $this->clientId = config('sso.github.client_id');
        $this->clientSecret = config('sso.github.client_secret');
        $this->redirectUri = config('sso.github.redirect');
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
            'scope' => 'read:user user:email',
            'allow_signup' => 'true',
        ]);

        return redirect("https://github.com/login/oauth/authorize?$query");
    }

    public function callback(Request $request)
    {
        try {
            $code = $request->get('code');

            if (!$code) {
                return $this->formatResponse(false, 400, 'Authorization code not found.');
            }

            $tokenResponse = Http::asForm()
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->post('https://github.com/login/oauth/access_token', [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'redirect_uri' => $this->redirectUri,
                    'code' => $code,
                ]);

            if (!$tokenResponse->successful()) {
                return $this->formatResponse(false, $tokenResponse->status(), 'Failed to retrieve access token.', $tokenResponse->json());
            }

            $accessToken = $tokenResponse->json()['access_token'];

            $userResponse = Http::withToken($accessToken)
                ->acceptJson()
                ->get('https://api.github.com/user');

            if (!$userResponse->successful()) {
                return $this->formatResponse(false, $userResponse->status(), 'Failed to retrieve user profile.', $userResponse->json());
            }

            $emailsResponse = Http::withToken($accessToken)
                ->acceptJson()
                ->get('https://api.github.com/user/emails');

            if (!$emailsResponse->successful()) {
                return $this->formatResponse(false, $emailsResponse->status(), 'Failed to retrieve user emails.', $emailsResponse->json());
            }

            $primaryEmail = collect($emailsResponse->json())->firstWhere('primary', true)['email'] ?? null;

            $user = $userResponse->json();

            return $this->formatResponse(
                true,
                200,
                'GitHub login successful.',
                [
                    'id' => $user['id'],
                    'name' => $user['name'] ?? $user['login'],
                    'email' => $primaryEmail,
                    'avatar' => $user['avatar_url'] ?? null,
                    'raw' => $user,
                ]
            );
        } catch (\Exception $e) {
            return $this->formatResponse(false, 500, 'Exception occurred: ' . $e->getMessage());
        }
    }
}