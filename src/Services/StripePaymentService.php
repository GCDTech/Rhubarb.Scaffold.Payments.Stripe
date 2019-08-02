<?php

namespace Gcd\Scaffold\Payments\Stripe\Services;

use Gcd\Scaffold\Payments\Stripe\Settings\StripeSettings;
use Gcd\Scaffold\Payments\UI\Entities\PaymentEntity;
use Stripe\Customer;
use Stripe\Issuing\Card;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripePaymentService extends PaymentEntity
{
    public function __construct()
    {
        $stripeSettings = StripeSettings::singleton();

        Stripe::setApiKey($stripeSettings->secretKey);
    }

    public function startPayment(PaymentEntity $entity) : PaymentEntity
     {
         // Throw an exception if the customerID && cardID isn't supplied OR paymentToken isn't supplied

         // Expect the token to be setup with the name / address if it's required
         if ($entity->providerIdentifier == PaymentEntity::TYPE_TOKEN) {
             $customer = Customer::create([
                 "email" => $entity->emailAddress,
                 "phone" => $entity->phone
             ]);
             $card = Customer::createSource(
                 $customer->id,
                 [
                     'source' => $entity->providerIdentifier,
                 ]
             );
         } else if ($entity->providerIdentifierType == PaymentEntity::TYPE_CUSTOMER) {
             $customer = Customer::retrieve($entity->providerIdentifier);
             $card = Card::retrieve($customer->default_source);
         }
         else if ($entity->providerIdentifierType == PaymentEntity::TYPE_CARD) {
             $card = Card::retrieve($entity->providerIdentifier);
             $customer = Customer::retrieve($card->Customer->id);

             // We want to create the payment intent based on the stored card details
             $stripeParams['customer'] = $customer->id;
             $stripeParams['payment_method'] = $card->id;
         } else {
             throw; // Throw an exception as we do not know the card type
         }

         $stripeIntent = PaymentIntent::create([
             'description' => $entity->description,
             'amount' => $entity->amount * 100,
             'currency' => $entity->currency,
             'customer' => $customer->id,
             'payment_method' => $card->id,
             'confirmation_method' => 'manual',
             'confirm' => true,
         ]);

         // Populate entities

         // Call use case to save payment tracking information for creation

         return $entity;
     }
}