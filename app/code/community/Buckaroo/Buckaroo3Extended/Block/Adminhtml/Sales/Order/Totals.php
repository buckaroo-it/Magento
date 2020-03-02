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
  class Buckaroo_Buckaroo3Extended_Block_Adminhtml_Sales_Order_Totals extends Mage_Adminhtml_Block_Sales_Order_Totals{
    /**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function _initTotals()
    {
        $orderId = $this->getOrder()->getIncrementId();

        parent::_initTotals();
        if($this->getSource()->getBaseBuckarooAlreadyPaid()){
          $this->_totals['alreadyPaid'] = new Varien_Object(array(
              'code'      => 'already_paid',
              'strong'    => true,
              'value'     => $this->getSource()->getBuckarooAlreadyPaid(),
              'base_value'=> $this->getSource()->getBaseBuckarooAlreadyPaid(),
              'label'     => $this->helper('sales')->__('Paid with Giftcard'),
              'area'      => 'footer'
          ));
        }

        return $this;
    }

}