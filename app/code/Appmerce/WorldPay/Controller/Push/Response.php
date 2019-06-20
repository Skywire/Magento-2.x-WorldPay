<?php
/**
 * Copyright © 2019 Appmerce - Applications for Ecommerce
 * http://www.appmerce.com
 */
namespace Appmerce\WorldPay\Controller\Push;

class Response extends \Appmerce\WorldPay\Controller\Worldpay
{
    // Local constants
    const STATUS_SUCCESS = 'Success';
    const STATUS_CANCELLED = 'Cancelled';
    const STATUS_OPEN = 'Open';
    const STATUS_FAILURE = 'Failure';
    const STATUS_EXPIRED = 'Expired';
    
    /**
     * Cancel payment
     *
     * @return void
     */
    public function execute()
    {
        $this->log->addDebug(__('Processing WorldPay response...'));
        $params = $this->getRequest()->getParams();
        $this->log->addDebug(json_encode((array)$params));
        $url = $this->getApiUrl('cancel');
        $return = '<meta http-equiv="refresh" content="0;url=' . $url . '" />';

        if (isset($params['MC_orderId'])) {
            $transactionId = isset($params['transId']) ? $params['transId'] : '0';
            $order = $this->_getOrder()->loadByIncrementId($params['MC_orderId']);
            $storeId = $order->getStoreId();

            if ($this->verifyData($params, $order)) {
                switch ($params['transStatus']) {
                    case 'Y' :
                        switch ($params['authMode']) {

                            // Authorize
                            case 'E' :
                                $note = __('WorldPay: Payment success');
                                $this->processPending($order, $note, $transactionId);
                                $url = $this->getApiUrl('success', $storeId);
                                $return = '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
                                break;

                            // Capture
                            case 'A' :
                            default :
                                $note = __('WorldPay: Payment success');
                                $this->processSuccess($order, $note, $transactionId);
                                $url = $this->getApiUrl('success', $storeId);
                                $return = '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
                        }
                        break;

                    case 'C' :
                    default :
                        $note = __('WorldPay: Payment canceled');
                        $this->processCancel($order, $transactionId);
                }
            }
        }

        $this->getResponse()->setBody($return);
    }

    /**
     * Verify if data is from WorldPay
     */
    public function verifyData($params, $order)
    {
        $return = true;

        // Check dataset
        if (empty($params)) {
            $return = false;
        }

        // Check MC_orderId
        if (!isset($params['MC_orderId']) || empty($params['MC_orderId'])) {
            $return = false;
        }

        // Check callbackPW
        if (!isset($params['callbackPW']) || $params['callbackPW'] != $this->api->getConfigData('callback_pw', $order->getStoreId())) {
            $return = false;
        }

        return $return;
    }
}
