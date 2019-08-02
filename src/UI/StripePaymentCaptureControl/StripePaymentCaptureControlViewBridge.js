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
        createPaymentMethod: function(paymentEntity) {
            return new Promise(function (resolve, reject) {
                this.stripe.createPaymentMethod('card', this.cardElement).then(
                    function (result) {
                        paymentEntity.providerPaymentMethodIdentifier = result.paymentMethod.id;
                        this.onPaymentMethodCreated(paymentEntity);
                        resolve(paymentEntity);
                    }.bind(this), function () {
                        reject();
                    }.bind(this));
            }.bind(this));
        },
        attemptPayment: function(paymentEntity) {
            return new Promise(function(resolve, reject){
                if (!paymentEntity.providerPaymentMethodIdentifier){
                    this.createPaymentMethod(paymentEntity).then(function(paymentEntity){
                        this.confirmPaymentOnServer(paymentEntity).then(resolve, reject);
                    }.bind(this), function(){
                        reject();
                    }.bind(this));
                } else {
                    this.confirmPaymentOnServer(paymentEntity).then(resolve, reject);
                }
            }.bind(this));
        },
        confirmPaymentOnServer: function(paymentEntity){
            return new Promise(function(resolve, reject){
                this.raiseServerEvent('confirmPayment', paymentEntity, function(paymentEntity){
                    this.onPaymentEntityStatusUpdated(paymentEntity).then(resolve);
                }.bind(this), function(){
                    reject();
                })
            }.bind(this));
        }
    };
}, rhubarb.viewBridgeClasses.PaymentCaptureControlViewBridge);