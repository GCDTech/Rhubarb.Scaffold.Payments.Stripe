<?php

namespace Gcd\Scaffold\Payments\Stripe\Services;

use Gcd\Scaffold\Payments\Services\PaymentService;
use Gcd\Scaffold\Payments\Stripe\Settings\StripeSettings;
use Gcd\Scaffold\Payments\UI\Entities\PaymentEntity;
use function GuzzleHttp\Psr7\str;
use Rhubarb\Leaf\Controls\Common\DateTime\Date;
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
         try {
             $stripeIntent = PaymentIntent::create([
                 'description' => $entity->description,
                 'amount' => $entity->amount * 100,
                 'currency' => $entity->currency,
                 'confirmation_method' => 'manual',
                 'payment_method' => $entity->providerPaymentMethodIdentifier,
                 'capture_method' => ($entity->autoSettle) ? 'automatic' : 'manual'
             ]);

             $entity = $this->syncEntity($entity, $stripeIntent);

             // Extra property used to save a round trip if startPayment and confirmPayment
             // are called one after the other.
             $entity->stripeIntent = $stripeIntent;

         } catch (\Exception $er){
             $entity->status = PaymentEntity::STATUS_FAILED;
             $entity->error = $er->getMessage();
         }

         return $entity;
     }

    public function confirmPayment(PaymentEntity $entity): PaymentEntity
    {
        if (isset($entity->stripeIntent)){
            $stripeIntent = $entity->stripeIntent;
        } else {
            $stripeIntent = PaymentIntent::retrieve($entity->providerIdentifier);
        }

        $entity = $this->syncEntity($entity, $stripeIntent);

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

    private function syncEntity(PaymentEntity $entity, $stripeIntent) {
        // Populate entities
        $paymentMethod = PaymentMethod::retrieve($stripeIntent->payment_method);
        $expiry = new \DateTime($paymentMethod->card->exp_year."-".$paymentMethod->card->exp_month."-01");

        $entity->provider = 'Stripe';
        $entity->providerIdentifier = $stripeIntent->id;
        $entity->providerPublicIdentifier = $stripeIntent->client_secret;
        $entity->providerPaymentMethodIdentifier = $stripeIntent->payment_method;
        $entity->providerPaymentMethodType = $stripeIntent->payment_method_types[0];
        $entity->status = $stripeIntent->status;
        $entity->cardExpiry = $expiry->format("m/y");
        $entity->cardType = $paymentMethod->card->brand;
        $entity->cardLastFourDigits = $paymentMethod->card->last4;

        $entity->addressCity = isset($entity->addressCity) ? $entity->addressCity : $paymentMethod->billing_details->address->city;
        $entity->addressLine1 = isset($entity->addressLine1) ? $entity->addressLine1 : $paymentMethod->billing_details->address->line1;
        $entity->addressLine2 = isset($entity->addressLine2) ? $entity->addressLine2 : $paymentMethod->billing_details->address->line2;
        $entity->addressPostCode = isset($entity->addressPostCode) ? $entity->addressPostCode : $paymentMethod->billing_details->address->postal_code;
        $entity->fullName = isset($entity->addressPostCode) ? $entity->addressPostCode : $paymentMethod->billing_details->name;
        $entity->emailAddress = isset($entity->addressPostCode) ? $entity->addressPostCode : $paymentMethod->billing_details->phone;
        $entity->phone = isset($entity->addressPostCode) ? $entity->addressPostCode : $paymentMethod->billing_details->email;

        return $entity;
    }
}