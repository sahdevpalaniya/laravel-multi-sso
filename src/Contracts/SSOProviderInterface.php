<?php

namespace Sahdev\SSO\Contracts;

use Illuminate\Http\Request;

interface SSOProviderInterface
{
    public function redirect();
    public function callback(Request $request);
}
