rhubarb.vb.create('StripeAuthenticationCaptureControlViewBridge', function(parent) {
    return {
        onReady:function() {
            parent.onReady.call(this);

            this.stripe = Stripe(this.model.stripePublicKey);
        },
        startCustomerAuthentication: function(paymentEntity) {
            return new Promise(function(resolve, reject){
                this.stripe.handleCardAction(paymentEntity.providerPublicIdentifier).then(function(result){
                    if (result.error){
                        paymentEntity.error = result.error.message;
                        reject(paymentEntity)
                    } else {
                        resolve(paymentEntity);
                    }
                }, function() {
                    reject(paymentEntity);
                });
            }.bind(this));
        }
    };
}, rhubarb.viewBridgeClasses.AuthenticationCaptureControlViewBridge);