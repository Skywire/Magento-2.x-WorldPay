<?php
/**
 * Copyright © 2019 Appmerce - Applications for Ecommerce
 * http://www.appmerce.com
 */
namespace Appmerce\WorldPay\Model;

class Worldpay extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_WORLDPAY_CODE = 'appmerce_worldpay';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_WORLDPAY_CODE;
    
    /**
     * @var boolean
     */
    protected $_canRefund = false;

    /**
     * @var boolean
     */
    protected $_canRefundInvoicePartial = false;

    /**
     * @var boolean
     */
    protected $_canUseInternal = false;
}
