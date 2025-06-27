<?php
namespace Sahdev\SSO\Facades;

use Illuminate\Support\Facades\Facade;

class SSO extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'sso';
    }
}
