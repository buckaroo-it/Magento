<?php
/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_Buckaroo3Extended_BuckarooAdminhtml_GiftcardController extends Mage_Adminhtml_Controller_Action
{
    protected function _construct()
    {
        $this->setUsedModuleName('TIG_Buckaroo3Extended');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/system/buckaroo_giftcard');
    }

    protected function _initAction()
    {
        $this->_title($this->__('System'))
             ->_title($this->__('Buckaroo Giftcards'));

        $this->loadLayout()
             ->_setActiveMenu('system/buckaroo_giftcards')
             ->_addBreadcrumb(
                 Mage::helper('adminhtml')->__('System'),
                 Mage::helper('adminhtml')->__('System')
             )
             ->_addBreadcrumb(
                 Mage::helper('buckaroo3extended')->__('Giftcards'),
                 Mage::helper('buckaroo3extended')->__('Giftcards')
             );

        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()->renderLayout();

        return $this;
    }

    public function newAction()
    {
        $this->_redirect('*/*/edit');

        return $this;
    }

    public function editAction()
    {
        $giftcard = Mage::getModel('buckaroo3extended/giftcard');
        $id = $this->getRequest()->getParam('entity_id');

        if ($id) {
            $giftcard->load($id);
        }

        Mage::register('current_giftcard', $giftcard);
        $this->_initAction()->renderLayout();

        return $this;
    }

    public function gridAction()
    {
        $this->loadLayout()->renderLayout();

        return $this;
    }

    public function saveAction()
    {
        $id = $this->getRequest()->getParam('entity_id');

        $giftcard = Mage::getModel('buckaroo3extended/giftcard');
        if ($id) {
            $giftcard->load($id);
        }

        $data = $this->getRequest()->getParam('giftcard');
        if (!is_array($data)) {
            Mage::getSingleton('adminhtml/session')->addError(
                $this->__('There was an error saving your giftcard. please check all fields and try again.')
            );
            $this->_redirect('*/*/edit', array('entity_id' => $id));

            return $this;
        }

        foreach ($data as $field => $value) {
            $giftcard->setDataUsingMethod($field, $value);
        }

        try {
            $giftcard->save();
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('*/*/edit', array('entity_id' => $id));

            return $this;
        }

        Mage::getSingleton('adminhtml/session')->addSuccess(
            $this->__('Product was saved successfully.')
        );

        $this->_redirect('*/*/index');
        return $this;
    }
}
