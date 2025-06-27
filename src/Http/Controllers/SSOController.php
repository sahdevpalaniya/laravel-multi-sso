<?php
namespace Sahdev\SSO\Http\Controllers;

use Illuminate\Routing\Controller;
use Sahdev\SSO\Services\SSOManager;

class SSOController extends Controller
{
    public function redirect($provider)
    {
        return SSOManager::getProvider($provider)->redirect();
    }

    public function callback($provider)
    {
        $user = SSOManager::getProvider($provider)->callback();
        // Handle login / registration
        return response()->json($user);
    }
}