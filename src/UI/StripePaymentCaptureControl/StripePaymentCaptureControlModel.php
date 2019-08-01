<?php

namespace Gcd\Scaffold\Payments\UI\StripePaymentCaptureControl;

use Gcd\Scaffold\Payments\UI\PaymentCaptureControl\PaymentCaptureControlModel;
use Rhubarb\Crown\Events\Event;

class StripePaymentCaptureControlModel extends PaymentCaptureControlModel
{
    /**
     * @var Event Called when the UI is ready to confirm a payment. Accepts a PaymentEntity as an argument.
     */
    public $confirmPaymentEvent;

    public function __construct()
    {
        $this->confirmPaymentEvent = new Event();
    }
}