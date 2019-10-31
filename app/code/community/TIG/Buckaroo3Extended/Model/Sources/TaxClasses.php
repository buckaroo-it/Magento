<?php 
class TIG_Buckaroo3Extended_Model_Sources_TaxClasses
{
    public function toOptionArray()
    {
        //@codingStandardsIgnoreStart
        $collection = Mage::getModel('tax/class')->getCollection()
            ->distinct(true)
            ->addFieldToFilter('class_type', array('like' => 'PRODUCT'))
            ->load();
        //@codingStandardsIgnoreEnd

        $classes = $collection->getColumnValues('class_id');
        
        $optionArray = array();
        $optionArray[''] = array('value' => '', 'label' => Mage::helper('buckaroo3extended')->__('None'));
        foreach ($classes as $class) {
            if (empty($class)) {
                continue;
            }

            $optionArray[$class] = array(
                'value' => $class,
                'label' => $this->getTaxClassName($class)
            );
        }
       
        return $optionArray;
    }

    /**
     * @param $class
     *
     * @return string
     */
    protected function getTaxClassName($class)
    {
        /** @var Mage_Tax_Model_Class $taxClass */
        $taxClass = Mage::getModel('tax/class')->load($class);
        $className = $taxClass->getClassName();

        return $className;
    }
}
