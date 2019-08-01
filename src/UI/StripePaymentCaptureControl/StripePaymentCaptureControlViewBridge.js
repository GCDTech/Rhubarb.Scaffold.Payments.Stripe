rhubarb.vb.create('StripePaymentCaptureControlViewBridge', function(parent) {
    return {
        onReady:function() {
            parent.onReady.call(this);

            this.stripe = Stripe(this.model.stripePublicKey);

            this.elements = this.stripe.elements();
            this.cardElement = this.elements.create('card');
            this.cardElement.mount(this.viewNode);
        }
    };
}, rhubarb.viewBridgeClasses.PaymentCaptureControlViewBridge)