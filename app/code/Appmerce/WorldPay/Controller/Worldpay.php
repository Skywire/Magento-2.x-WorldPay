<?php
/**
 * Copyright © 2019 Appmerce - Applications for Ecommerce
 * http://www.appmerce.com
 */
namespace Appmerce\WorldPay\Controller;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

abstract class Worldpay extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    // Local constants
    const API_CONTROLLER_PATH = 'worldpay/api/';
    const PUSH_CONTROLLER_PATH = 'worldpay/push/';
    
    // Source Model Appmerce_WorldPay_Model_Source_TestResult
    const TEST_AUTHORISED = 'AUTHORISED';
    const TEST_CAPTURED = 'CAPTURED';
    const TEST_ERROR = 'ERROR';
    const TEST_REFUSED = 'REFUSED';

    // Response codes
    const STATUS_TRUE = 'True';
    const STATUS_FALSE = 'False';
    
    // Default order statuses
    const DEFAULT_STATUS_PENDING = 'pending';
    const DEFAULT_STATUS_PENDING_PAYMENT = 'pending_payment';
    const DEFAULT_STATUS_PROCESSING = 'processing';

    protected $log;

    /**
     * @var \Appmerce\WorldPay\Model\Worldpay
     */
    protected $api;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Psr\Log\LoggerInterface $log
     * @param \Appmerce\WorldPay\Model\Worldpay $api
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $log,
        \Appmerce\WorldPay\Model\Worldpay $api,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->_storeManager = $storeManager;
        parent::__construct($context);
        $this->log = $log;
        $this->checkoutSession = $checkoutSession;
        $this->localeResolver = $localeResolver;
        $this->api = $api;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
    
    /**
     * @return \Magento\Quote\Model\Quote
     */
    protected function _getQuote()
    {
        return $this->_objectManager->get('Magento\Quote\Model\Quote');
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    protected function _getOrder()
    {
        return $this->_objectManager->get('Magento\Sales\Model\Order');
    }

    /**
     * Return gateways
     */
    public function getHostedGateways()
    {
        return array(
            'frontend' => array(
                'live' => 'https://secure.worldpay.com/wcc/purchase',
                'test' => 'https://secure-test.worldpay.com/wcc/purchase',
            ),
            'backend' => array(
                'live' => 'https://secure.worldpay.com/wcc/itransaction',
                'test' => 'https://secure-test.worldpay.com/wcc/itransaction',
            ),
            'iadmin' => array(
                'live' => 'https://secure.worldpay.com/wcc/iadmin',
                'test' => 'https://secure-test.worldpay.com/wcc/iadmin',
            ),
            'xml' => array(
                'live' => 'https://secure.worldpay.com/jsp/merchant/xml/paymentService.jsp',
                'test' => 'https://secure-test.worldpay.com/jsp/merchant/xml/paymentService.jsp',
            )
        );
    }

    /**
     * Get gateway Url
     *
     * @return string
     */
    public function getGatewayUrl($end)
    {
        $gateways = $this->getHostedGateways();
        $test = $this->api->getConfigData('test_flag') ? 'test' : 'live';
        return $url = $gateways[$end][$test];
    }

    /**
     * Decide grand total
     *
     * @return float
     */
    public function getGrandTotal($order)
    {
        if ($this->api->getConfigData('base_currency')) {
            $grandTotal = $order->getBaseGrandTotal();
        }
        else {
            $grandTotal = $order->getGrandTotal();
        }
        return round($grandTotal, 2);
    }

    /**
     * Decide currency code type
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        if ($this->api->getConfigData('base_currency')) {
            $currencyCode = $this->_storeManager->getStore()->getBaseCurrencyCode();
        }
        else {
            $currencyCode = $this->_storeManager->getStore()->getCurrentCurrencyCode();
        }
        return $currencyCode;
    }

    /**
     * Generates array of fields for redirect form
     *
     * @param string $code
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getPostData($order)
    {
        $storeId = $order->getStoreId();
        $address = $order->getBillingAddress();

        $fields = array();

        // Custom
        $fields[] = array('name' => 'MC_orderId', 'value' => $order->getIncrementId());
        $fields[] = array('name' => 'MC_callback', 'value' => $this->getPushUrl('response', $storeId, true));

        // Required
        $fields[] = array('name' => 'instId', 'value' => $this->api->getConfigData('instid', $storeId));
        $fields[] = array('name' => 'cartId', 'value' => substr($order->getIncrementId(), 0, 255));
        $fields[] = array('name' => 'amount', 'value' => $this->getGrandTotal($order));
        $fields[] = array('name' => 'currency', 'value' => $this->getCurrencyCode());

        // Optional
        $fields[] = array('name' => 'authMode', 'value' => 'A'); // E or A
        $fields[] = array('name' => 'desc', 'value' => substr(__('Order %1', $order->getIncrementId()), 0, 32));

        $locale = $this->localeResolver->getLocale();
        $fields[] = array('name' => 'lang', 'value' => substr(str_replace('_', '-', $locale), 0, 6));

        $accid = $this->api->getConfigData('accid', $storeId);
        if (!empty($accid)) {
            $fields[] = array('name' => 'accId1', 'value' => $accid);
        }
        
        // Customer details
        $street = $address->getStreet();
        $street_1 = isset($street[0]) ? $street[0] : '';
        $street_2 = isset($street[1]) ? $street[1] : '';
        $fields[] = array('name' => 'address1', 'value' => substr($street_1, 0, 84));
        $fields[] = array('name' => 'address2', 'value' => substr($street_2, 0, 84));
        $fields[] = array('name' => 'town', 'value' => substr($address->getCity(), 0, 30));
        $fields[] = array('name' => 'region', 'value' => substr($address->getRegion(), 0, 30));
        $fields[] = array('name' => 'postcode', 'value' => substr($address->getPostcode(), 0, 12));
        $fields[] = array('name' => 'country', 'value' => substr($address->getCountryId(), 0, 2));
        $fields[] = array('name' => 'tel', 'value' => substr($address->getTelephone(), 0, 30));
        $fields[] = array('name' => 'fax', 'value' => substr($address->getFax(), 0, 30));
        $fields[] = array('name' => 'name', 'value' => substr(str_replace('"', '', $address->getName()), 0, 40));

        // Get from $order directly
        $fields[] = array('name' => 'email', 'value' => substr($order->getCustomerEmail(), 0, 80));

        // Fix contact, etc.
        $fields[] = array('name' => 'fixContact', 'value' => '');
        $fields[] = array('name' => 'hideContact', 'value' => '');
        $fields[] = array('name' => 'hideCurrency', 'value' => '');
        $fields[] = array('name' => 'noLanguageMenu', 'value' => '');

        // Dynamic MD5 Signature
        if ($this->api->getConfigData('signature', $storeId) == true) {
            $fields[] = array('name' => 'signatureFields', 'value' => 'instId:amount:currency:cartId:MC_orderId:email');
            $fields[] = array('name' => 'signature', 'value' => md5(
                        $this->api->getConfigData('md5_secret', $storeId) . ':' 
                        . $this->api->getConfigData('merchant_id', $storeId) . ':' 
                        . $this->getGrandTotal($order) . ':' 
                        . $this->getCurrencyCode() . ':' 
                        . substr($order->getIncrementId(), 0, 255) . ':' 
                        . $order->getIncrementId() . ':' 
                        . substr($order->getCustomerEmail(), 0, 80)));
        }

        // AuthValidTo Security, UNIX in milliseconds
        $time = (time() + 1800) * 1000;
        $fields[] = array('name' => 'authValidTo', 'value' => $time);
        
        // Test Mode
        if ($this->api->getConfigData('test_flag') == true) {
            $fields[] = array('name' => 'testMode', 'value' => 100);
            $fields[] = array('name' => 'name', 'value' => $this->api->getConfigData('test_result', $storeId));
            $fields[] = array('name' => 'subst', 'value' => 'yes');
        }
        
        return $fields;
    }

    /**
     * Return redirect URL for method
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     */
    protected function getJsonData($order)
    {
        return array('url' => $this->getGatewayUrl('frontend'), 'fields' => $this->getPostData($order));
    }
    
    /**
     * Return URLs
     * 
     * @param string $key
     * @param int $storeId
     * @param bool $noSid
     * @return mixed
     */
    public function getApiUrl($key, $storeId = null, $noSid = false)
    {
        return $this->_url->getUrl(self::API_CONTROLLER_PATH . $key, ['_store' => $storeId, '_secure' => true, '_nosid' => $noSid]);
    }
    
    public function getPushUrl($key, $storeId = null, $noSid = false)
    {
        return $this->_url->getUrl(self::PUSH_CONTROLLER_PATH . $key, ['_store' => $storeId, '_secure' => true, '_nosid' => $noSid]);
    }
    
    /**
     * Get order pending payment status
     */
    public function getPendingStatus()
    {
        $status = $this->api->getConfigData('pending_status');
        if (empty($status)) {
            $status = self::DEFAULT_STATUS_PENDING_PAYMENT;
        }
        return $status;
    }
    
    /**
     * Get order processing status
     */
    public function getProcessingStatus()
    {
        $status = $this->api->getConfigData('processing_status');
        if (empty($status)) {
            $status = self::DEFAULT_STATUS_PROCESSING;
        }
        return $status;
    }
    
    /**
     * Success process
     * [single-method]
     *
     * Update succesful (paid) orders, send order email, create invoice
     * and send invoice email. Restore quote and clear cart.
     *
     * @param $order object Mage_Sales_Model_Order
     * @param $note string Backend order history note
     * @param $transactionId string Transaction ID
     */
    public function processSuccess($order, $note, $transactionId)
    {
        $this->processCheck($order);
	    	$transactionId = (string)$transactionId;
        $order->getPayment()->setAdditionalInformation('transaction_id', $transactionId)
                            ->setLastTransId($transactionId)
                            ->save();

        // Set Total Paid & Due
        // (The invoice will do this.)
        // $amount = $order->getGrandTotal();
        // $order->setTotalPaid($amount);

        // Set processing status
        $status = $this->getProcessingStatus();
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
              ->setStatus($status)
              ->addStatusHistoryComment($note)
              ->setIsCustomerNotified(true)
              ->save();

        // Create invoice
        if ($this->api->getConfigData('invoice_create')) {
            $this->processInvoice($order);
            $this->log->addDebug(__('Invoice created.'));
        }
    }

    /**
     * Create automatic invoice
     * [single-method]
     *
     * @param $order object
     */
    public function processInvoice($order)
    {
        if (!$order->hasInvoices() && $order->canInvoice()) {
            $invoice = $order->prepareInvoice();
            if ($invoice->getTotalQty() > 0) {
                $transactionId = $order->getPayment()->getTransactionId();
                $this->log->addDebug($transactionId);
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                $invoice->setTransactionId($transactionId);
                $invoice->register();
                
                $transactionSave = $this->_objectManager->create('Magento\Framework\DB\Transaction')
                                        ->addObject($invoice)
                                        ->addObject($order)
                                        ->save();

                // Send invoice email
                if (!$invoice->getEmailSent() && $this->api->getConfigData('invoice_email')) {
                    
                    // Nothing yet.
                    return;
                }
                $invoice->save();
            }
        }
    }

    /**
     * Pending process
     *
     * Update orders with explicit payment pending status. Restore quote.
     *
     * @param $order object
     * @param $note string Backend order history note
     * @param $transactionId string Transaction ID
     */
    public function processPending($order, $note, $transactionId)
    {
        $this->processCheck($order);
	    	$transactionId = (string)$transactionId;
        $order->getPayment()->setAdditionalInformation('transaction_id', $transactionId)
                            ->setLastTransId($transactionId)
                            ->save();

        // Set pending_payment status
        $status = $this->getPendingStatus();
        $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
              ->setStatus($status)
              ->addStatusHistoryComment($note)
              ->setIsCustomerNotified(true)
              ->save();
    }

    /**
     * Cancel process
     *
     * Update failed, cancelled, declined, rejected etc. orders. Cancel
     * the order and show user message. Restore quote.
     *
     * @param $order object
     * @param $note string Backend order history note
     * @param $transactionId string Transaction ID
     */
    public function processCancel($order, $transactionId)
    {
        $this->processCheck($order);
	    	$transactionId = (string)$transactionId;
        $order->getPayment()->setAdditionalInformation('transaction_id', $transactionId)
                            ->setLastTransId($transactionId)
                            ->save();

        // Cancel order
        $order->cancel()->save();
    }

    /**
     * Check order state
     *
     * If the order state (not status) is already one of:
     * canceled, closed, holded or completed,
     * then we do not update the order status anymore.
     *
     * @param $order object
     */
    public function processCheck($order)
    {
        if ($order->getId()) {
            $state = $order->getState();
            switch ($state) {
                
                // Do not allow further updates; prevent double invoices
                case \Magento\Sales\Model\Order::STATE_HOLDED :
                case \Magento\Sales\Model\Order::STATE_CANCELED :
                case \Magento\Sales\Model\Order::STATE_CLOSED :
                case \Magento\Sales\Model\Order::STATE_COMPLETE :
                    
                    // Kill process
                    $this->log->addDebug(__('Payment already processed.'));
                    http_response_code(200);
                    break;
                    
                // Allow updates
                case \Magento\Sales\Model\Order::STATE_NEW :
                case \Magento\Sales\Model\Order::STATE_PROCESSING :
                    break;
            }
        }
        else {
            
            // No order
            $this->log->addDebug(__('Order not found.'));
            http_response_code(200);
        }
        
        return $this;
    }

    /**
     * Restore cart
     */
    public function restoreCart()
    {
        $lastQuoteId = $this->checkoutSession->getLastQuoteId();
        if ($quote = $this->_getQuote()->loadByIdWithoutStore($lastQuoteId)) {
            $quote->setIsActive(true)
                  ->setReservedOrderId(null)
                  ->save();
            $this->checkoutSession->setQuoteId($lastQuoteId);
        }

        $message = __('Payment failed. Please try again.');
        $this->messageManager->addError($message);
    }
}
