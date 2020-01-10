<?php
class TIG_Buckaroo3Extended_Block_Adminhtml_System_Config_Hint
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_template = 'buckaroo3extended/system/config/hint.phtml';

    public $methods = array(
        'amex',
        'directdebit',
        'giropay',
        'ideal',
        'idealprocessing',
        'mastercard',
        'cartebancaire',
        'cartebleue',
        'onlinegiro',
        'paypal',
        'payconiq',
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
        'klarna',
        'pospayment',
        'capayablepostpay',
        'capayableinstallments',
        'kbc',
        'p24',
        'dankort',
        'nexi',
        'creditcard',
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
