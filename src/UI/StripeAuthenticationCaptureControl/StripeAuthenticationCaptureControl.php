<?php

namespace Gcd\Scaffold\Payments\Stripe\UI\StripeAuthenticationCaptureControl;

use Gcd\Scaffold\Payments\Stripe\Services\StripePaymentService;
use Gcd\Scaffold\Payments\Stripe\Settings\StripeSettings;
use Gcd\Scaffold\Payments\UI\AuthenticationCaptureControl\AuthenticationCaptureControl;
use Gcd\Scaffold\Payments\UI\Entities\SetupEntity;
use Gcd\Scaffold\Payments\UI\PaymentCaptureControl\PaymentCaptureControl;
use Rhubarb\Leaf\Leaves\LeafModel;
use Stripe\SetupIntent;

class StripeAuthenticationCaptureControl extends AuthenticationCaptureControl
{
    /**
     * @var StripeAuthenticationCaptureControlModel
     */
    protected $model;


    /**
     * Returns the name of the standard view used for this leaf.
     *
     * @return string
     */
    protected function getViewClass()
    {
        return StripeAuthenticationCaptureControlView::class;
    }

    /**
     * Should return a class that derives from LeafModel
     *
     * @return LeafModel
     */
    protected function createModel()
    {
        return new StripeAuthenticationCaptureControlModel();
    }

    protected function onModelCreated()
    {
        parent::onModelCreated();

        $this->model->stripePublicKey = StripeSettings::singleton()->publicKey;
    }

    protected function getProviderService()
    {
        return new StripePaymentService();
    }
}