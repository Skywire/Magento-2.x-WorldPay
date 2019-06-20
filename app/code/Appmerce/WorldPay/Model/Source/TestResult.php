<?php
/**
 * Copyright © 2019 Appmerce - Applications for Ecommerce
 * http://www.appmerce.com
 */
namespace Appmerce\WorldPay\Model\Source;

/**
 * Pending Payment Statuses source model
 */
class TestResult implements \Magento\Framework\Option\ArrayInterface
{
    const TEST_AUTHORISED = 'AUTHORISED';
    const TEST_CAPTURED = 'CAPTURED';
    const TEST_ERROR = 'ERROR';
    const TEST_REFUSED = 'REFUSED';

    /**
     * Possible environment types
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::TEST_AUTHORISED,
                'label' => 'Authorised'
            ],
            [
                'value' => self::TEST_CAPTURED,
                'label' => 'Captured'
            ],
            [
                'value' => self::TEST_ERROR,
                'label' => 'Error'
            ],
            [
                'value' => self::TEST_REFUSED,
                'label' => 'Refused'
            ]
        ];
    }
}
