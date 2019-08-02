<?php

namespace Gcd\Scaffold\Payments\Stripe\Services;

use Gcd\Scaffold\Payments\Services\PaymentService;
use Gcd\Scaffold\Payments\Stripe\Settings\StripeSettings;
use Gcd\Scaffold\Payments\UI\Entities\PaymentEntity;
use Stripe\Customer;
use Stripe\Issuing\Card;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class StripePaymentService extends PaymentService
{
    public function __construct()
    {
        $stripeSettings = StripeSettings::singleton();

        Stripe::setApiKey($stripeSettings->secretKey);
    }

    public function startPayment(PaymentEntity $entity) : PaymentEntity
     {
         // Throw an exception if the customerID && cardID isn't supplied OR paymentToken isn't supplied

         $stripeIntent = PaymentIntent::create([
             'description' => $entity->description,
             'amount' => $entity->amount * 100,
             'currency' => $entity->currency,
             'confirmation_method' => 'manual',
             'payment_method' => $entity->providerPaymentMethodIdentifier,
             'capture_method' => ($entity->autoSettle) ? 'automatic' : 'manual'
         ]);

         // Populate entities
         $entity->providerIdentifier = $stripeIntent->id;

         // Extra property used to save a round trip if startPayment and confirmPayment
         // are called one after the other.
         $entity->stripeIntent = $stripeIntent;

         // Call use case to save payment tracking information for creation

         return $entity;
     }

    public function confirmPayment(PaymentEntity $entity): PaymentEntity
    {
        if (isset($entity->stripeIntent)){
            $stripeIntent = $entity->stripeIntent;
        } else {
            $stripeIntent = PaymentIntent::retrieve($entity->providerIdentifier);
        }

        try {
            $stripeIntent->confirm();

            if (($stripeIntent->status == 'requires_action' || $stripeIntent->status == 'requires_source_action') &&
                $stripeIntent->next_action->type == 'use_stripe_sdk') {
                $entity->status = PaymentEntity::STATUS_AWAITING_AUTHENTICATION;
                $entity->providerPublicIdentifier = $stripeIntent->client_secret;
            } else if ($stripeIntent->status == 'succeeded') {
                $entity->status = PaymentEntity::STATUS_SUCCESS;
            } else {
                $entity->status = PaymentEntity::STATUS_FAILED;
            }
        } catch (\Exception $er){
            $entity->status = PaymentEntity::STATUS_FAILED;
            $entity->error = $er->getMessage();
        }

        return $entity;
    }

    public function refundPayment(PaymentEntity $entity): PaymentEntity
    {
        // TODO: Implement refundPayment() method.
    }

    public function settlePayment(PaymentEntity $entity): PaymentEntity
    {
        // TODO: Implement settlePayment() method.
    }
}