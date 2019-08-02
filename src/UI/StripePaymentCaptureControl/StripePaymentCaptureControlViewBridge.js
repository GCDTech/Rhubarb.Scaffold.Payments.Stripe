rhubarb.vb.create('StripePaymentCaptureControlViewBridge', function(parent) {
    return {
        onReady:function() {
            parent.onReady.call(this);

            this.stripe = Stripe(this.model.stripePublicKey);

            this.elements = this.stripe.elements();
            this.cardElement = this.elements.create('card', {
                hidePostalCode: !this.model.showPostalCode
            });

            this.cardElement.mount(this.viewNode);
        },
        confirmPayment: function(paymentEntity) {

            if (paymentEntity){
                this.model.paymentEntity = paymentEntity;
            }

            return new Promise(function(resolve, reject) {
                this.stripe.createPaymentMethod('card', this.cardElement).then(
                    function (result) {
                        this.model.paymentEntity.providerPaymentMethodIdentifier = result.paymentMethod.id;
                        this.raiseServerEvent('confirmPayment', this.model.paymentEntity, function (newPaymentEntity) {
                            this.model.paymentEntity = newPaymentEntity;
                            resolve(this.model.paymentEntity);
                    }.bind(this), function () {
                        reject();
                    }.bind(this));
                }.bind(this));
            }.bind(this));
        }
    };
}, rhubarb.viewBridgeClasses.PaymentCaptureControlViewBridge);