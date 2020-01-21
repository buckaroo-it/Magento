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
class Buckaroo_Buckaroo3Extended_Block_Adminhtml_Giftcard_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = Mage::getModel(
            'varien/data_form',
            array(
                'id'      => 'edit_form',
                'action'  => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
                'method'  => 'post',
                'enctype' => 'multipart/form-data',
            )
        );

        $data = Mage::registry('current_giftcard')->getData();
        $form->setDataObject(Mage::registry('current_giftcard'));

        $fieldset = $form->addFieldset(
            'buckaroo3extended_form',
            array(
                'legend' => Mage::helper('buckaroo3extended')->__('General Information')
            )
        );

        if (!empty($data['entity_id'])) {
            $fieldset->addField(
                'entity_id', 'hidden',
                array(
                    'label'    => Mage::helper('buckaroo3extended')->__('ID'),
                    'required' => false,
                    'name'     => 'entity_id',
                    'value'    => '',
                )
            );
        }

        $fieldset->addField(
            'servicecode', 'text',
            array(
                'label'    => Mage::helper('buckaroo3extended')->__('Service Code'),
                'class'    => 'required-entry validate-alpha',
                'required' => true,
                'name'     => 'giftcard[servicecode]',
            )
        );

        $fieldset->addField(
            'label', 'text',
            array(
                'label'    => Mage::helper('buckaroo3extended')->__('Name'),
                'class'    => 'required-entry',
                'required' => true,
                'name'     => 'giftcard[label]',
            )
        );

        $form->addValues($data);
        $form->setUseContainer(true);
        $this->setForm($form);

        return $this;
    }
}
