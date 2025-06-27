# SSO-Package for Laravel

## Overview

This Laravel Composer package provides seamless integration for multiple Single Sign-On (SSO) providers. The supported providers in this version include:

* GitHub
* Google
* JumpCloud
* LinkedIn
* Twitter
* AWS Cognito

The package is designed to be modular, scalable, and easy to configure for any Laravel application.

---

## Installation

### Step 1: Require the Package

```bash
composer require sahdevpalaniya/laravel-multi-sso
```

### Step 2: Publish the Configuration

Publish the configuration file to your Laravel project:

```bash
php artisan vendor:publish --tag=sso-config
```

This will create a configuration file (`sso.php`) in the `config` directory.

### Step 3: Configure the Environment

Add the necessary environment variables for your desired SSO providers in the `.env` file. Below are the detailed configurations for each provider.

---

## Supported Providers

### GitHub

**Configuration:**

```env
SSO_GITHUB_CLIENT_ID=your-github-client-id
SSO_GITHUB_CLIENT_SECRET=your-github-client-secret
SSO_GITHUB_REDIRECT=https://yourdomain.com/sso/github/callback
```

**Routes:**

```php
Route::get('/sso/github/redirect', function () {
    return SSO::driver('github')->redirect();
})->name('github.redirect');

Route::get('/sso/github/callback', function (Request $request) {
    $githubData = SSO::driver('github')->callback($request);
    return response()->json($githubData->getData()->data);
})->name('github.callback');
```

---

### Google

**Configuration:**

```env
SSO_GOOGLE_CLIENT_ID=your-google-client-id
SSO_GOOGLE_CLIENT_SECRET=your-google-client-secret
SSO_GOOGLE_REDIRECT=https://yourdomain.com/sso/google/callback
```

**Routes:**

```php
Route::get('/sso/google/redirect', function () {
    return SSO::driver('google')->redirect();
})->name('google.redirect');

Route::get('/sso/google/callback', function (Request $request) {
    $googleData = SSO::driver('google')->callback($request);
    return response()->json($googleData->getData()->data);
})->name('google.callback');
```

---

### JumpCloud

**Configuration:**

```env
SSO_JUMPCLOUD_ENTITY_ID=your-jumpcloud-entity-id
SSO_JUMPCLOUD_SSO_URL=your-jumpcloud-sso-url
SSO_JUMPCLOUD_CERTIFICATE=your-jumpcloud-certificate
```

**Routes:**

```php
Route::get('/sso/jumpcloud/redirect', function () {
    return SSO::driver('jumpcloud')->redirect();
})->name('jumpcloud.redirect');

Route::get('/sso/jumpcloud/callback', function (Request $request) {
    $jumpcloudData = SSO::driver('jumpcloud')->callback($request);
    return response()->json($jumpcloudData->getData()->data);
})->name('jumpcloud.callback');
```

---

### LinkedIn

**Configuration:**

```env
SSO_LINKEDIN_CLIENT_ID=your-linkedin-client-id
SSO_LINKEDIN_CLIENT_SECRET=your-linkedin-client-secret
SSO_LINKEDIN_REDIRECT=https://yourdomain.com/sso/linkedin/callback
```

**Routes:**

```php
Route::get('/sso/linkedin/redirect', function () {
    return SSO::driver('linkedin')->redirect();
})->name('linkedin.redirect');

Route::get('/sso/linkedin/callback', function (Request $request) {
    $linkedinData = SSO::driver('linkedin')->callback($request);
    return response()->json($linkedinData->getData()->data);
})->name('linkedin.callback');
```

---

### Twitter

**Configuration:**

```env
SSO_TWITTER_CLIENT_ID=your-twitter-client-id
SSO_TWITTER_CLIENT_SECRET=your-twitter-client-secret
SSO_TWITTER_REDIRECT=https://yourdomain.com/sso/twitter/callback
```

**Routes:**

```php
Route::get('/sso/twitter/redirect', function () {
    return SSO::driver('twitter')->redirect();
})->name('twitter.redirect');

Route::get('/sso/twitter/callback', function (Request $request) {
    $twitterData = SSO::driver('twitter')->callback($request);
    return response()->json($twitterData->getData()->data);
})->name('twitter.callback');
```

---

### AWS Cognito

**Configuration:**

```env
SSO_AWS_COGNITO_CLIENT_ID=your-aws-cognito-client-id
SSO_AWS_COGNITO_CLIENT_SECRET=your-aws-cognito-client-secret
SSO_AWS_COGNITO_REGION=your-aws-region
SSO_AWS_COGNITO_USER_POOL_ID=your-aws-cognito-user-pool-id
```

**Routes:**

#### Register User

```php
Route::post('/sso/aws/register', function (Request $request) {
    $response = SSO::driver('aws')->register($request);
    return response()->json($response);
})->name('aws.register');
```

| Parameter      | Type     | Required | Description                              |
| -------------- | -------- | -------- | ---------------------------------------- |
| `username`     | `string` | Yes      | Unique username for the user.            |
| `password`     | `string` | Yes      | Password for the user account.           |
| `email`        | `string` | Yes      | User's email address.                    |
| `phone_number` | `string` | No       | User's phone number.                     |
| `full_name`    | `string` | No       | Full name of the user.                   |
| `first_name`   | `string` | No       | First name of the user.                  |
| `last_name`    | `string` | No       | Last name of the user.                   |
| `locale`       | `string` | No       | User's preferred locale (e.g., `en-US`). |

#### Login User

```php
Route::post('/sso/aws/login', function (Request $request) {
    $response = SSO::driver('aws')->login($request);
    return response()->json($response);
})->name('aws.login');
```

| Parameter  | Type     | Required | Description      |
| ---------- | -------- | -------- | ---------------- |
| `username` | `string` | Yes      | User's username. |
| `password` | `string` | Yes      | User's password. |

#### Get User Details

```php
Route::post('/sso/aws/user-details', function (Request $request) {
    $response = SSO::driver('aws')->getUserDetails($request);
    return response()->json($response);
})->name('aws.user.details');
```

| Parameter      | Type     | Required | Description                        |
| -------------- | -------- | -------- | ---------------------------------- |
| `access_token` | `string` | Yes      | Access token received after login. |

#### Logout User

```php
Route::post('/sso/aws/logout', function (Request $request) {
    $response = SSO::driver('aws')->logout($request);
    return response()->json($response);
})->name('aws.logout');
```

| Parameter      | Type     | Required | Description                        |
| -------------- | -------- | -------- | ---------------------------------- |
| `access_token` | `string` | Yes      | Access token received after login. |

---

## Attribute Mapping for AWS Cognito

The `attributes` array in the `register` API accepts the following keys:

| Input Key      | Required | Description              |
| -------------- | -------- | ------------------------ |
| `email`        | Yes      | User's email address.    |
| `phone_number` | No       | User's phone number.     |
| `full_name`    | No       | Full name of the user.   |
| `first_name`   | No       | First name of the user.  |
| `last_name`    | No       | Last name of the user.   |
| `locale`       | No       | User's preferred locale. |

---

## Contributing

Contributions are welcome! Feel free to fork the repository and submit PRs.

---

## Changelog

See [CHANGELOG](CHANGELOG) for a detailed history of changes.

---

## License

This project is open-source and licensed under the MIT License.

---

## Support

For any questions or issues, please create an [issue](https://github.com/sahdevpalaniya/laravel-multi-sso/issues) in this repository or contact [sahdevsinhpalaniya98@gmail.com](mailto:sahdevsinhpalaniya98@gmail.com).