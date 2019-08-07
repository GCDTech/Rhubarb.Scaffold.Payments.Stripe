<?php

namespace Gcd\Scaffold\Payments\Stripe;

use Gcd\Scaffold\Payments\PaymentsModule;
use Gcd\Scaffold\Payments\Stripe\UI\StripeAuthenticationCaptureControl\StripeAuthenticationCaptureControl;
use Rhubarb\Crown\Module;

class StripePaymentsModule extends Module
{
    protected function getModules()
    {
        return [
            new PaymentsModule()
        ];
    }

    protected function initialise()
    {
        parent::initialise();

        // This ensures the follow up screen can handle stripe payments without being extended.
        PaymentsModule::registerAuthenticationControl("Stripe", StripeAuthenticationCaptureControl::class);
    }


}