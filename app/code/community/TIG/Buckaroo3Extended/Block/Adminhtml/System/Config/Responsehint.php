<?php
class TIG_Buckaroo3Extended_Block_Adminhtml_System_Config_Responsehint
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_template = 'buckaroo3extended/system/config/responsehint.phtml';

    public $methods = array(
        'amex',
        'directdebit',
        'giropay',
        'ideal',
        'mastercard',
        'cartebancaire',
        'cartebleue',
        'onlinegiro',
        'paypal',
        'paysafecard',
        'sofortueberweisung',
        'transfer',
        'visa',
        'payperemail',
        'paymentguarantee',
        'giftcards',
        'empayment',
        'maestro',
        'visaelectron',
        'vpay',
        'bancontactmrcash',
        'eps',
        'afterpay',
        'afterpay2',
        'afterpay20',
        'masterpass',
        'applepay',
    );

    public $services = array(
        'refund',
    );

    public $config = array(
        'advanced',
        'certificate',
    );

    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    // @codingStandardsIgnoreLine
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }
}
