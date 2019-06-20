<?php
/**
 * Copyright © 2019 Appmerce - Applications for Ecommerce
 * http://www.appmerce.com
 */
namespace Appmerce\WorldPay\Controller\Api;

class Success extends \Appmerce\WorldPay\Controller\Worldpay
{
    /**
     * Success payment
     *
     * @return void
     */
    public function execute()
    {
        // Processed by push notification
        $this->_redirect('checkout/onepage/success', array('_secure' => true));
    }

}
