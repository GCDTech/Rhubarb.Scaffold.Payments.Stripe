<?php

namespace Gcd\Scaffold\Payments\Stripe\Services;

use Gcd\Scaffold\Payments\Services\PaymentService;
use Gcd\Scaffold\Payments\Stripe\Settings\StripeSettings;
use Gcd\Scaffold\Payments\UI\Entities\PaymentEntity;
use Gcd\Scaffold\Payments\UI\Entities\SetupEntity;
use function GuzzleHttp\Psr7\str;
use Rhubarb\Leaf\Controls\Common\DateTime\Date;
use Stripe\Customer;
use Stripe\Issuing\Card;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\SetupIntent;
use Stripe\Stripe;

class StripePaymentService extends PaymentService
{
    public function __construct()
    {
        $stripeSettings = StripeSettings::singleton();

        Stripe::setApiKey($stripeSettings->secretKey);
    }

    public function attachPaymentToCustomer($paymentId, $customerId = null): string
    {
        if (!$customerId){
            $customer = Customer::create(['payment_method' => $paymentId]);
            return $customer->id;
        }

        PaymentMethod::retrieve($paymentId)->attach(['customer' => $customerId]);

        return $customerId;
    }

    public function createSetupIntent(): SetupEntity
    {
        $setupIntent = SetupIntent::create();

        $entity = new SetupEntity();
        $entity->providerIdentifier = $setupIntent->id;
        $entity->providerPublicIdentifier = $setupIntent->client_secret;

        return $entity;
    }

    public function startPayment(PaymentEntity $entity) : PaymentEntity
    {
        try {
            $data = [
                'description' => $entity->description,
                'amount' => $entity->amount * 100,
                'currency' => $entity->currency,
                'confirmation_method' => 'manual',
                'payment_method' => $entity->providerPaymentMethodIdentifier,
                'capture_method' => ($entity->autoSettle) ? 'automatic' : 'manual',
                'off_session' => !$entity->onSession,          // Off session means the customer isn't around
                'confirm' => !$entity->onSession               // confirm controls if the payment is confirmed
                // automatically.
            ];

            if ($entity->providerCustomerId) {
                $data['customer'] = $entity->providerCustomerId;
            }

            $stripeIntent = PaymentIntent::create($data);

            $entity = $this->syncEntityWithIntent($entity, $stripeIntent);

            // Extra property used to save a round trip if startPayment and confirmPayment
            // are called one after the other.
            $entity->stripeIntent = $stripeIntent;
        } catch (\Stripe\Error\Card $er){
            if ($er->stripeCode == "authentication_required"){
                // Unfortunately as the Stripe SDK throws an exception when creating off session
                // payments that require authentication, we don't have the payment intent object
                // to sync with. In fact we don't even have a payment intent id - but we can grab
                // the raw API response body to get past this.
                $rawBody = json_decode($er->httpBody, false);

                $entity->providerIdentifier = $rawBody->payment_intent->id;
                $entity->providerPublicIdentifier = $rawBody->payment_intent->client_secret;
                $entity->status = PaymentEntity::STATUS_AWAITING_AUTHENTICATION;
            } else {
                $entity->status = PaymentEntity::STATUS_FAILED;
                $entity->error = $er->getMessage();
            }
        } catch (\Exception $er) {
            $entity->status = PaymentEntity::STATUS_FAILED;
            $entity->error = $er->getMessage();
        }

        return $entity;
    }

    public function confirmPayment(PaymentEntity $entity): PaymentEntity
    {
        if ($entity->status == PaymentEntity::STATUS_SUCCESS || $entity->status == PaymentEntity::STATUS_FAILED){
            // In these states, confirm cannot do anything more. confirmPayment might get called with a successful
            // entity because for off-session payments, Stripe require us to 'auto confirm' the payment at point of
            // creation. Therefore the calling code, unless it understands this, might treat it like a one off payment
            // where we startPayment and then try calling confirmPayment.
            return $entity;
        }

        if (isset($entity->stripeIntent)){
            $stripeIntent = $entity->stripeIntent;
        } else {
            $stripeIntent = PaymentIntent::retrieve($entity->providerIdentifier);
        }

        $entity = $this->syncEntityWithIntent($entity, $stripeIntent);

        try {
            $stripeIntent->confirm();
        } catch (\Exception $er){
        }

        $entity = $this->syncEntityWithIntent($entity, $stripeIntent);

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

    private function syncEntityWithIntent(PaymentEntity $entity, PaymentIntent $stripeIntent) {
        // Populate entities
        $paymentMethod = PaymentMethod::retrieve($stripeIntent->payment_method);
        $expiry = new \DateTime($paymentMethod->card->exp_year."-".$paymentMethod->card->exp_month."-01");

        $entity->provider = 'Stripe';
        $entity->providerIdentifier = $stripeIntent->id;
        $entity->providerPublicIdentifier = $stripeIntent->client_secret;
        $entity->providerPaymentMethodIdentifier = $stripeIntent->payment_method;
        $entity->providerPaymentMethodType = $stripeIntent->payment_method_types[0];

        switch($stripeIntent->status){
            case "succeeded":
                $entity->status = PaymentEntity::STATUS_SUCCESS;
                break;
            case "requires_action":
            case "requires_source_action":
                $entity->status = PaymentEntity::STATUS_AWAITING_AUTHENTICATION;
                break;
            case "failed":
                $entity->status = PaymentEntity::STATUS_FAILED;
                $entity->error = $stripeIntent->last_payment_error;
                break;
        }

        if (($stripeIntent->status == 'requires_action' || $stripeIntent->status == 'requires_source_action') &&
            $stripeIntent->next_action->type == 'use_stripe_sdk') {
            $entity->providerPublicIdentifier = $stripeIntent->client_secret;
        }

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