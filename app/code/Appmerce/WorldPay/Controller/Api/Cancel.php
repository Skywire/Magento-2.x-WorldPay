<?php
/**
 * Copyright © 2019 Appmerce - Applications for Ecommerce
 * http://www.appmerce.com
 */
namespace Appmerce\WorldPay\Controller\Api;

class Cancel extends \Appmerce\WorldPay\Controller\Worldpay
{
    /**
     * Cancel payment
     *
     * @return void
     */
    public function execute()
    {
        // Canceled by push notification
        $this->restoreCart();
        $this->_redirect('checkout/cart', array('_secure' => true));
    }

}
