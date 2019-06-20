<?php
/**
 * Copyright © 2019 Appmerce - Applications for Ecommerce
 * http://www.appmerce.com
 */
namespace Appmerce\WorldPay\Controller\Api;

class PlaceOrder extends \Appmerce\WorldPay\Controller\Worldpay
{    
    /**
     * Return JSON form fields
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $response = FALSE;
        
        try {
            $incrementId = $this->checkoutSession->getLastRealOrder()->getIncrementId();
            if ($order = $this->_getOrder()->loadByIncrementId($incrementId)) {
                $response = $this->getJsonData($order);
            }
        } 
        catch (\Exception $e) {
            $this->log->critical($e);
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}
