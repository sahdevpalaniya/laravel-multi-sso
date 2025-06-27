<?php

namespace Sahdev\SSO\Services;

use InvalidArgumentException;
use Sahdev\SSO\Providers\GitHubProvider;
use Sahdev\SSO\Providers\GoogleProvider;
use Sahdev\SSO\Providers\TwitterProvider;
use Sahdev\SSO\Providers\JumpCloudProvider;
use Sahdev\SSO\Providers\LinkedinProvider;
use Sahdev\SSO\Providers\AWSProvider;

class SSOManager
{
    public function driver(string $provider)
    {
        return match (strtolower($provider)) {
            'google' => new GoogleProvider(),
            'github' => new GitHubProvider(),
            'twitter' => new TwitterProvider(),
            'linkedin' => new LinkedinProvider(),
            'jumpcloud' => new JumpCloudProvider(),
            'aws' => new AWSProvider(),
            default => throw new InvalidArgumentException("SSO provider [$provider] is not supported."),
        };
    }
}
