/**
 * Copyright © 2019 Appmerce - Applications for Ecommerce
 * http://www.appmerce.com
 */
define(
        [
            'jquery',
            'Magento_Checkout/js/view/payment/default',
            'Magento_Checkout/js/action/place-order',
            'Magento_Checkout/js/action/select-payment-method',
            'Magento_Customer/js/model/customer',
            'Magento_Checkout/js/checkout-data',
            'Magento_Checkout/js/model/payment/additional-validators',
            'mage/url',
            "mage/validation"
        ],
        function ($, Component, placeOrderAction, selectPaymentMethodAction, customer, checkoutData, additionalValidators, url) {
            'use strict';
            return Component.extend({
                defaults: {
                    template: 'Appmerce_WorldPay/payment/worldpay-form'
                },
                initObservable: function () {
                    this._super();
                    return this;
                },
                getData: function () {
                    return {
                        "method": this.item.method,
                        "additional_data": null
                    };
                },
                validate: function () {
                    var form = 'form[data-role=worldpay-form]';
                    return $(form).validation() && $(form).validation('isValid');
                },

                /**
                 * Place order (overridden).
                 */
                placeOrder: function (data, event) {
                    var self = this, placeOrder;

                    if (event) {
                        event.preventDefault();
                    }

                    if (this.validate() && additionalValidators.validate()) {
                        this.isPlaceOrderActionAllowed(false);
                        placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                        $.when(placeOrder)
                                .fail(
                                        function () {
                                            self.isPlaceOrderActionAllowed(true);
                                        }
                                ).done(
                                this.afterPlaceOrder.bind(this)
                                );
                        return true;
                    }
                    return false;
                },

                selectPaymentMethod: function () {
                    selectPaymentMethodAction(this.getData());
                    checkoutData.setSelectedPaymentMethod(this.item.method);
                    return true;
                },

                afterPlaceOrder: function () {
                    $.get(url.build('worldpay/api/placeOrder/'))
                            .done(function (data) {
                                var gatewayForm = 'form#worldpay-gateway-form';

                                // Append form action
                                $(gatewayForm).attr('action', data.url);

                                // Append form fields
                                $.each(data.fields, function (key, field) {
                                    $('<input>').attr({
                                        type: 'hidden',
                                        name: field.name,
                                        value: field.value
                                    }).appendTo(gatewayForm);
                                });

                                // Submit form to gateway (redirect user)
                                $(gatewayForm).submit();
                            });
                }
            });
        }
);
