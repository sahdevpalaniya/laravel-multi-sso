<?php

namespace Sahdev\SSO\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Sahdev\SSO\Contracts\SSOProviderInterface;

class LinkedinProvider implements SSOProviderInterface
{
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $authUrl = 'https://www.linkedin.com/oauth/v2/authorization';
    protected $tokenUrl = 'https://www.linkedin.com/oauth/v2/accessToken';
    protected $userInfoUrl = 'https://api.linkedin.com/v2/me';
    protected $emailUrl = 'https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))';

    public function __construct()
    {
        $this->clientId = config('sso.linkedin.client_id');
        $this->clientSecret = config('sso.linkedin.client_secret');
        $this->redirectUri = config('sso.linkedin.redirect');
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
        $scope = 'r_liteprofile r_emailaddress';
        $state = csrf_token();

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => $scope,
            'state' => $state,
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

            $userInfo = $this->getUserInfo($accessToken);
            $emailInfo = $this->getUserEmail($accessToken);

            $userDetails = [
                "id" => $userInfo['id'] ?? null,
                "first_name" => $userInfo['localizedFirstName'] ?? null,
                "last_name" => $userInfo['localizedLastName'] ?? null,
                "email" => $emailInfo ?? null,
            ];

            return $this->formatResponse(true, 200, 'LinkedIn login successful.', $userDetails);
        } catch (\Exception $e) {
            return $this->formatResponse(false, 500, 'Exception occurred: ' . $e->getMessage());
        }
    }

    protected function getAccessToken($code)
    {
        $response = Http::asForm()->post($this->tokenUrl, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to get access token: ' . $response->body());
        }

        return $response->json()['access_token'];
    }

    protected function getUserInfo($accessToken)
    {
        $response = Http::withToken($accessToken)->get($this->userInfoUrl);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch user info: ' . $response->body());
        }

        return $response->json();
    }

    protected function getUserEmail($accessToken)
    {
        $response = Http::withToken($accessToken)->get($this->emailUrl);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch user email: ' . $response->body());
        }

        return $response->json()['elements'][0]['handle~']['emailAddress'] ?? null;
    }
}