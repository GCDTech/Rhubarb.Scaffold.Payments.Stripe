<?php

namespace Gcd\Scaffold\Payments\Stripe\UI\StripePaymentCaptureControl;

use Gcd\Scaffold\Payments\Stripe\Services\StripePaymentService;
use Gcd\Scaffold\Payments\Stripe\Settings\StripeSettings;
use Gcd\Scaffold\Payments\UI\Entities\SetupEntity;
use Gcd\Scaffold\Payments\UI\PaymentCaptureControl\PaymentCaptureControl;
use Rhubarb\Leaf\Leaves\LeafModel;
use Stripe\SetupIntent;

class StripePaymentCaptureControl extends PaymentCaptureControl
{
    /**
     * @var StripePaymentCaptureControlModel
     */
    protected $model;

    public function __construct($name = null, $onSession = true, $showPostcode = false)
    {
        parent::__construct($name, $onSession, function() use ($showPostcode){
            $this->model->showPostcode = $showPostcode;
        });
    }


    /**
     * Returns the name of the standard view used for this leaf.
     *
     * @return string
     */
    protected function getViewClass()
    {
        return StripePaymentCaptureControlView::class;
    }

    /**
     * Should return a class that derives from LeafModel
     *
     * @return LeafModel
     */
    protected function createModel()
    {
        return new StripePaymentCaptureControlModel();
    }

    protected function onModelCreated()
    {
        parent::onModelCreated();

        $this->model->stripePublicKey = StripeSettings::singleton()->publicKey;

        $this->model->createSetupIntentEvent->attachHandler(function($providerCustomerId){
            $stripeService = $this->getProviderService();
            return $stripeService->createSetupIntent($providerCustomerId);
        });

        $this->model->setupIntentCompletedEvent->attachHandler(function($setupEntity){
            $setupEntity = SetupEntity::castFromObject($setupEntity);
            $stripeService = $this->getProviderService();
            $paymentMethod = $stripeService->attachPaymentToCustomer($setupEntity->providerPaymentMethodIdentifier,
                $setupEntity->providerCustomerId);
            $setupEntity->providerCustomerId = $paymentMethod->customer;
            $setupEntity->cardLastFourDigits = $paymentMethod->card->last4;
            $setupEntity->cardExpiryMonth = $paymentMethod->card->exp_month;
            $setupEntity->cardExpiryYear = $paymentMethod->card->exp_year;
            $setupEntity->cardType = $paymentMethod->card->brand;

            return $setupEntity;
        });
    }

    protected function getProviderService()
    {
        return new StripePaymentService();
    }
}