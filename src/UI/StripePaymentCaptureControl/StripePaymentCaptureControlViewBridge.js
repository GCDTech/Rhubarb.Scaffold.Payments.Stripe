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
        setupPaymentMethod: function(providerCustomerId) {
            return new Promise(function(resolve, reject){
                this.raiseServerEvent('createSetupIntent', providerCustomerId, function(setupEntity){
                    this.stripe.handleCardSetup(setupEntity.providerPublicIdentifier, this.cardElement).then(function(result){
                        if (result.error) {
                            setupEntity.error = result.error.message;
                            reject(setupEntity);
                        } else {
                            setupEntity.providerPaymentMethodIdentifier = result.setupIntent.payment_method;
                            this.raiseServerEvent("setupIntentCompleted", setupEntity, function(setupEntity){
                                this.onPaymentMethodCreated(setupEntity);
                                resolve(setupEntity);
                            }.bind(this));
                        }
                    }.bind(this), reject);
                }.bind(this));
            }.bind(this));
        },
        authenticatePayment: function(paymentEntity) {
            return this.findChildViewBridge('Authenticate').startCustomerAuthentication(paymentEntity);
        },
        createPaymentMethod: function(paymentEntity) {
            return new Promise(function (resolve, reject) {
                this.stripe.createPaymentMethod('card', this.cardElement).then(
                    function (result) {
                        paymentEntity.cardType = result.paymentMethod.card.brand;
                        paymentEntity.cardLastFourDigits = result.paymentMethod.card.last4;
                        paymentEntity.cardExpiryMonth = result.paymentMethod.card.exp_month;
                        paymentEntity.cardExpiryYear = result.paymentMethod.card.exp_year;
                        paymentEntity.providerPaymentMethodIdentifier = result.paymentMethod.id;
                        resolve(paymentEntity);
                    }.bind(this), function () {
                        reject(paymentEntity);
                    });
            }.bind(this));
        },
        attemptPayment: function(paymentEntity) {
            return new Promise(function(resolve, reject){
                if (!paymentEntity.providerPaymentMethodIdentifier){
                    this.createPaymentMethod(paymentEntity).then(function(paymentEntity){
                        this.confirmPaymentOnServer(paymentEntity).then(resolve, reject);
                    }.bind(this), function(){
                        reject(paymentEntity);
                    }.bind(this));
                } else {
                    this.confirmPaymentOnServer(paymentEntity).then(resolve, reject);
                }
            }.bind(this));
        },
        confirmPaymentOnServer: function(paymentEntity){
            return new Promise(function(resolve, reject){
                this.raiseServerEvent('confirmPayment', paymentEntity, function(paymentEntity){
                    this.onPaymentEntityStatusUpdated(paymentEntity).then(resolve, reject);
                }.bind(this), function(){
                    reject(paymentEntity);
                })
            }.bind(this));
        }
    };
}, rhubarb.viewBridgeClasses.PaymentCaptureControlViewBridge);