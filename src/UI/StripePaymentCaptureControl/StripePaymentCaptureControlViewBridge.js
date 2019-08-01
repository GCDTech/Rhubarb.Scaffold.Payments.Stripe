rhubarb.vb.create('StripePaymentCaptureControlViewBridge', function(parent) {
    return {
        attachEvents:function() {
            parent.attachEvents.call(this);
        }
    };
}, rhubarb.viewBridgeClasses.PaymentCaptureControlViewBridge)