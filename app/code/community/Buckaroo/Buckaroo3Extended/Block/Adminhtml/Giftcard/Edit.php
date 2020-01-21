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
class Buckaroo_Buckaroo3Extended_Block_Adminhtml_Giftcard_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
 
        $this->_objectId = 'entity_id';
        $this->_blockGroup = 'buckaroo3extended';
        $this->_controller = 'adminhtml_giftcard';
        $this->_mode = 'edit';
    }
 
    public function getHeaderText()
    {
        $giftcard = Mage::registry('current_giftcard');
        
        if ($giftcard->getLabel()) {
            $headerText = Mage::helper('buckaroo3extended')->__('Edit Giftcard "%s"', $giftcard->getLabel());
            return $headerText;
        }
        
        $headerText = Mage::helper('buckaroo3extended')->__('Create new giftcard');
        return $headerText;
    }
}
