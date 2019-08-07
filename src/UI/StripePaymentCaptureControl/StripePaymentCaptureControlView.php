<?php

namespace Gcd\Scaffold\Payments\Stripe\UI\StripePaymentCaptureControl;

use Gcd\Scaffold\Payments\Stripe\UI\StripeAuthenticationCaptureControl\StripeAuthenticationCaptureControl;
use Gcd\Scaffold\Payments\UI\PaymentCaptureControl\PaymentCaptureControlView;

class StripePaymentCaptureControlView extends PaymentCaptureControlView
{
    /**
     * @var StripePaymentCaptureControlModel
     */
    protected $model;

    protected function createSubLeaves()
    {
        parent::createSubLeaves();

        $this->registerSubLeaf(
            new StripeAuthenticationCaptureControl("Authenticate")
        );
    }


    public function getDeploymentPackage()
    {
        $package = parent::getDeploymentPackage();
        $package->resourcesToDeploy[] = __DIR__.'/StripePaymentCaptureControlViewBridge.js';

        return $package;
    }

    protected function getViewBridgeName()
    {
        return "StripePaymentCaptureControlViewBridge";
    }

    protected function getAdditionalResourceUrls()
    {
        return ["https://js.stripe.com/v3/#.js"];
    }

    protected function printViewContent()
    {
        print $this->leaves["Authenticate"];
    }
}