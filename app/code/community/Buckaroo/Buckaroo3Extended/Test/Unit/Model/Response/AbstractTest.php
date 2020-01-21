<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * It is available through the world-wide-web at this URL:
 * https://tldrlegal.com/license/mit-license
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@buckaroo.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@buckaroo.nl for more information.
 *
 * @copyright Copyright (c) Buckaroo B.V.
 * @license   https://tldrlegal.com/license/mit-license
 */
class Buckaroo_Buckaroo3Extended_Test_Unit_Model_Response_AbstractTest
    extends Buckaroo_Buckaroo3Extended_Test_Framework_Buckaroo_Test_TestCase
{
    /** @var null|Buckaroo_Buckaroo3Extended_Model_Response_Abstract */
    protected $_instance = null;

    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $parameters = array(
                'debugEmail' => '',
                'response' => false,
                'XML' => false
            );

            $this->_instance = new Buckaroo_Buckaroo3Extended_Model_Response_Abstract($parameters);
        }

        return $this->_instance;
    }

    /**
     * @return array
     */
    public function getResponseFailureMessageProvider()
    {
        return array(
            'no failure message' => array(
                (Object)array('ServiceCode' => 'invalid_service_code'),
                null
            ),
            'afterpay failure message with colon' => array(
                (Object)array(
                    'ServiceCode' => 'afterpaydigiaccept',
                    'TransactionType' => 'C011',
                    'Status' => (Object)array(
                        'Code' => (Object)array(
                            'Code' => '490'
                        ),
                        'SubCode' => (Object)array(
                            'Code' => 'S996',
                            '_' => 'And error occured: Telefoonnummer is onjuist'
                        )
                    )
                ),
                'Telefoonnummer is onjuist'
            ),
            'afterpay failure message without colon' => array(
                (Object)array(
                    'ServiceCode' => 'afterpayacceptgiro',
                    'TransactionType' => 'C016',
                    'Status' => (Object)array(
                        'Code' => (Object)array(
                            'Code' => '490'
                        ),
                        'SubCode' => (Object)array(
                            'Code' => 'S996',
                            '_' => 'Address gegevens zijn onjuist'
                        )
                    )
                ),
                'Address gegevens zijn onjuist'
            ),
            'afterpay failure message invalid transactiontype' => array(
                (Object)array(
                    'ServiceCode' => 'afterpaydigiaccept',
                    'TransactionType' => 'I011',
                    'Status' => (Object)array(
                        'Code' => (Object)array(
                            'Code' => '490'
                        ),
                        'SubCode' => (Object)array(
                            'Code' => 'S996',
                            '_' => 'Geboortedatum is onjuist'
                        )
                    )
                ),
                null
            ),
            'klarna failure message' => array(
                (Object)array(
                    'ServiceCode' => 'klarna',
                    'ConsumerMessage' => (Object)array(
                        'HtmlText' => 'Klarna payment failure'
                    )
                ),
                'Klarna payment failure'
            ),
        );
    }

    /**
     * @param $response
     * @param $expected
     *
     * @dataProvider getResponseFailureMessageProvider
     */
    public function testGetResponseFailureMessage($response, $expected)
    {
        $this->registerMockSessions(array('checkout'));

        $instance = $this->_getInstance();
        $this->setProperty('_response', $response, $instance);
        $result = $this->invokeMethod($instance, 'getResponseFailureMessage');

        $this->assertEquals($expected, $result);
    }
}
