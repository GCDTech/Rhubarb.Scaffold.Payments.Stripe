<?php

namespace Gcd\Scaffold\Payments\Stripe\UI\StripePaymentCaptureControl;

use Gcd\Scaffold\Payments\UI\PaymentCaptureControl\PaymentCaptureControlModel;
use Rhubarb\Crown\Events\Event;

class StripePaymentCaptureControlModel extends PaymentCaptureControlModel
{
    /**
     * @var Event Called when the UI is ready to confirm a payment. Accepts a PaymentEntity as an argument.
     */
    public $confirmPaymentEvent;

    /**
     * @var Event Raised at the start of a setup card payment journey to satisfy the Stripe SDK
     *
     * Should return the client secret of the intent.
     */
    public $createSetupIntentEvent;

    public $setupIntentCompletedEvent;

    public $stripePublicKey;

    public $showPostcode;

    public function __construct()
    {
        $this->confirmPaymentEvent = new Event();
        $this->createSetupIntentEvent = new Event();
        $this->setupIntentCompletedEvent = new Event();
    }

    protected function getExposableModelProperties()
    {
        $list = parent::getExposableModelProperties();
        $list[] = "stripePublicKey";
        $list[] = "showPostcode";

        return $list;
    }


}