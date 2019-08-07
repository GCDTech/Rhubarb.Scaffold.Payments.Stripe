<?php

namespace Gcd\Scaffold\Payments\Stripe\UI\StripeAuthenticationCaptureControl;

use Gcd\Scaffold\Payments\UI\AuthenticationCaptureControl\AuthenticationCaptureControlView;

class StripeAuthenticationCaptureControlView extends AuthenticationCaptureControlView
{
    /**
     * @var StripeAuthenticationCaptureControlModel
     */
    protected $model;

    public function getDeploymentPackage()
    {
        $package = parent::getDeploymentPackage();
        $package->resourcesToDeploy[] = __DIR__.'/StripeAuthenticationCaptureControlViewBridge.js';

        return $package;
    }

    protected function getViewBridgeName()
    {
        return "StripeAuthenticationCaptureControlViewBridge";
    }

    protected function getAdditionalResourceUrls()
    {
        return ["https://js.stripe.com/v3/#.js"];
    }

    protected function printViewContent()
    {

    }
}