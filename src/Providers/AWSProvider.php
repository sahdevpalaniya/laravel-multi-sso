<?php

namespace Sahdev\SSO\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AWSProvider
{
    protected $region;
    protected $clientId;
    protected $clientSecret;

    protected $keyMapper = [
        'email' => 'email',
        'phone' => 'phone_number',
        'full_name' => 'name',
        'first_name' => 'given_name',
        'last_name' => 'family_name',
        'dob' => 'birthdate',
        'user_address' => 'address',
        'gender' => 'gender',
        'username' => 'preferred_username',
        'locale' => 'locale',
        'timezone' => 'timezone',
        'profile_picture' => 'picture',
        'website_url' => 'website',
    ];

    public function __construct()
    {
        $this->region = config('sso.aws.region');
        $this->clientId = config('sso.aws.client_id');
        $this->clientSecret = config('sso.aws.client_secret');
    }

    protected function calculateSecretHash($username)
    {
        return base64_encode(hash_hmac('sha256', $username . $this->clientId, $this->clientSecret, true));
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

    protected function mapAttributes(array $inputAttributes)
    {
        $mappedAttributes = [];

        foreach ($this->keyMapper as $userKey => $awsKey) {
            if (isset($inputAttributes[$userKey])) {
                $mappedAttributes[$awsKey] = $inputAttributes[$userKey];
            }
        }

        return $mappedAttributes;
    }

    public function register(Request $request)
    {
        try {
            $validationRules = [
                'username' => 'required|string|max:40',
                'password' => 'required|string|min:8',
                'email' => 'required|email',
                // Optional keys
                'phone' => 'nullable|string',
                'full_name' => 'nullable|string',
                'first_name' => 'nullable|string',
                'last_name' => 'nullable|string',
                'dob' => 'nullable|date',
                'user_address' => 'nullable|string',
                'gender' => 'nullable|string',
                'locale' => 'nullable|string',
                'timezone' => 'nullable|string',
                'profile_picture' => 'nullable|url',
                'website_url' => 'nullable|url',
            ];

            $validator = Validator::make($request->all(), $validationRules);

            if ($validator->fails()) {
                return $this->formatResponse('error', 400, 'Validation failed', $validator->errors());
            }

            $username = $request->username;
            $password = $request->password;
            $secretHash = $this->calculateSecretHash($username);

            // Map user-provided attributes to AWS Cognito attributes
            $mappedAttributes = $this->mapAttributes($request->all());

            $userAttributes = array_map(function ($key, $value) {
                return ['Name' => $key, 'Value' => $value];
            }, array_keys($mappedAttributes), $mappedAttributes);

            $endpoint = "https://cognito-idp.{$this->region}.amazonaws.com/";

            $response = Http::withHeaders([
                'Content-Type' => 'application/x-amz-json-1.1',
                'X-Amz-Target' => 'AWSCognitoIdentityProviderService.SignUp',
            ])->post($endpoint, [
                'ClientId' => $this->clientId,
                'Username' => $username,
                'Password' => $password,
                'SecretHash' => $secretHash,
                'UserAttributes' => $userAttributes,
            ]);

            if (!$response->successful()) {
                return $this->formatResponse('error', $response->status(), 'Registration failed', $response->json());
            }

            return $this->formatResponse('success', 200, 'Registration successful', $response->json());
        } catch (\Exception $e) {
            return $this->formatResponse('error', 500, 'An unexpected error occurred', ['exception' => $e->getMessage()]);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|max:40',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                return $this->formatResponse('error', 400, 'Validation failed', $validator->errors());
            }

            $username = $request->username;
            $password = $request->password;
            $secretHash = $this->calculateSecretHash($username);
            $endpoint = "https://cognito-idp.{$this->region}.amazonaws.com/";

            $response = Http::withHeaders([
                'Content-Type' => 'application/x-amz-json-1.1',
                'X-Amz-Target' => 'AWSCognitoIdentityProviderService.InitiateAuth',
            ])->post($endpoint, [
                'AuthFlow' => 'USER_PASSWORD_AUTH',
                'ClientId' => $this->clientId,
                'AuthParameters' => [
                    'USERNAME' => $username,
                    'PASSWORD' => $password,
                    'SECRET_HASH' => $secretHash,
                ],
            ]);

            if (!$response->successful()) {
                return $this->formatResponse('error', $response->status(), 'Login failed', $response->json());
            }

            $authData = $response->json();
            return $this->formatResponse('success', 200, 'Login successful', [
                'accessToken' => $authData['AuthenticationResult']['AccessToken'],
                'idToken' => $authData['AuthenticationResult']['IdToken'],
            ]);
        } catch (\Exception $e) {
            return $this->formatResponse('error', 500, 'An unexpected error occurred', ['exception' => $e->getMessage()]);
        }
    }

    public function logout(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'access_token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->formatResponse('error', 400, 'Validation failed', $validator->errors());
            }

            $accessToken = $request->access_token;
            $endpoint = "https://cognito-idp.{$this->region}.amazonaws.com/";

            $response = Http::withHeaders([
                'Content-Type' => 'application/x-amz-json-1.1',
                'X-Amz-Target' => 'AWSCognitoIdentityProviderService.GlobalSignOut',
            ])->post($endpoint, [
                'AccessToken' => $accessToken,
            ]);

            if (!$response->successful()) {
                return $this->formatResponse('error', $response->status(), 'Logout failed', $response->json());
            }

            return $this->formatResponse('success', 200, 'Logout successful');
        } catch (\Exception $e) {
            return $this->formatResponse('error', 500, 'An unexpected error occurred', ['exception' => $e->getMessage()]);
        }
    }

    public function getUserDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'access_token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->formatResponse('error', 400, 'Validation failed', $validator->errors());
            }

            $accessToken = $request->access_token;
            $endpoint = "https://cognito-idp.{$this->region}.amazonaws.com/";

            $response = Http::withHeaders([
                'Content-Type' => 'application/x-amz-json-1.1',
                'X-Amz-Target' => 'AWSCognitoIdentityProviderService.GetUser',
            ])->post($endpoint, [
                'AccessToken' => $accessToken,
            ]);

            if (!$response->successful()) {
                return $this->formatResponse('error', $response->status(), 'Failed to fetch user details', $response->json());
            }

            $userData = $response->json();
            $attributes = collect($userData['UserAttributes'])->mapWithKeys(function ($attribute) {
                return [$attribute['Name'] => $attribute['Value']];
            });

            return $this->formatResponse('success', 200, 'User details retrieved successfully', $attributes);
        } catch (\Exception $e) {
            return $this->formatResponse('error', 500, 'An unexpected error occurred', ['exception' => $e->getMessage()]);
        }
    }
}