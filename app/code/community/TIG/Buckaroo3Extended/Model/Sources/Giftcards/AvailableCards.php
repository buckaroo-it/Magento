<?php
class TIG_Buckaroo3Extended_Model_Sources_Giftcards_Availablecards
{
    public function toOptionArray()
    {
        $helper = Mage::helper('buckaroo3extended');
        $giftcardCollection = Mage::getResourceModel('buckaroo3extended/giftcard_collection');
        $giftcardCollection->addFieldToSelect('servicecode')
                           ->addFieldToSelect('label');
        
        $options = array();
        foreach ($giftcardCollection as $giftcard) {
            $options[] = array(
                'value' => $giftcard->getServicecode(),
                'label' => $giftcard->getlabel(),
            );
        }
        
        return $options;
    }
}
