<?php

namespace Gcd\Scaffold\Payments\UI\StripePaymentCaptureControl;

use Gcd\Scaffold\Payments\UI\Entities\PaymentEntity;
use Gcd\Scaffold\Payments\UI\PaymentCaptureControl\PaymentCaptureControl;
use Rhubarb\Leaf\Leaves\Leaf;
use Rhubarb\Leaf\Leaves\LeafModel;

class StripePaymentCaptureControl extends PaymentCaptureControl
{
    /**
     * @var StripePaymentCaptureControlModel
     */
    protected $model;

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

        $this->model->confirmPaymentEvent->attachHandler(function(PaymentEntity $paymentEntity){
            
        });
    }
}