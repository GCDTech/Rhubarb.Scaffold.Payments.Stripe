<?php

namespace Gcd\Scaffold\Payments\Stripe\UI\StripePaymentCaptureControl;

use Gcd\Scaffold\Payments\UI\PaymentCaptureControl\PaymentCaptureControlView;

class StripePaymentCaptureControlView extends PaymentCaptureControlView
{
    /**
     * @var StripePaymentCaptureControlModel
     */
    protected $model;

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
        ?>
        Stripe payment!
        <?php
    }
}