/**
 * Copyright © 2019 Appmerce - Applications for Ecommerce
 * http://www.appmerce.com
 */
define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
],
function (Component, rendererList) {
    'use strict';
    rendererList.push(
        {
            type: 'appmerce_worldpay',
            component: 'Appmerce_WorldPay/js/view/payment/method-renderer/worldpay-method'
        }
    );

    /** Add view logic here if needed */
        return Component.extend({});
    }
);