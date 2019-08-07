<?php

namespace Gcd\Scaffold\Payments\Stripe\UI\StripeAuthenticationCaptureControl;

use Gcd\Scaffold\Payments\UI\AuthenticationCaptureControl\AuthenticationCaptureControlModel;

class StripeAuthenticationCaptureControlModel extends AuthenticationCaptureControlModel
{
    public $stripePublicKey;

    protected function getExposableModelProperties()
    {
        $list = parent::getExposableModelProperties();
        $list[] = "stripePublicKey";

        return $list;
    }
}