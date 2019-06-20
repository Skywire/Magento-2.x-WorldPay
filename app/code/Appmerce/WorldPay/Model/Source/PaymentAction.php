<?php
/**
 * Copyright © 2019 Appmerce - Applications for Ecommerce
 * http://www.appmerce.com
 */
namespace Appmerce\WorldPay\Model\Source;

/**
 * Pending Payment Statuses source model
 */
class PaymentAction implements \Magento\Framework\Option\ArrayInterface
{
    const ACTION_AUTHORIZE = 'auth';
    const ACTION_AUTHORIZE_CAPTURE = 'capture';

    /**
     * Possible environment types
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ACTION_AUTHORIZE,
                'label' => 'Authorize Only'
            ],
            [
                'value' => self::ACTION_AUTHORIZE_CAPTURE,
                'label' => 'Authorize and Capture'
            ]
        ];
    }
}
