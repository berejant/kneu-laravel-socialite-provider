<?php

namespace SocialiteProviders\Kneu;

use SocialiteProviders\Manager\SocialiteWasCalled;

class KneuExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'kneu', __NAMESPACE__ . '\Provider'
        );
    }
}
