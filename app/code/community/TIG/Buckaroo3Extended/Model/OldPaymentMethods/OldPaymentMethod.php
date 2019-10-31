<?php
class TIG_Buckaroo3Extended_Model_OldPaymentMethods_OldPaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'oldBuckaroo';

    protected $_isGateway               = false;
    protected $_canAuthorize            = false;
    protected $_canCapture              = false;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = false;
    protected $_canUseForMultishipping  = false;
    protected $_canSaveCc               = false;

    // @codingStandardsIgnoreLine
    public function getOrderPlaceRedirectUrl()
    {

    }

    public function isAvailable($quote = null)
    {
        return false;
    }
}
